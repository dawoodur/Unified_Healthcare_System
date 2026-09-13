<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Rule-based FAQ search. It keeps the existing keyword-matching behavior,
 * while also checking the FAQ question/answer text so the Help Center search
 * remains useful in both supported locales. This is not an AI assistant.
 */
class FaqController extends Controller
{
    /** Shows the blank search form plus the full FAQ list (GET /help). */
    public function index()
    {
        $faqs = $this->faqList();

        return view('help.index', [
            'question' => null,
            'answer' => null,
            'allFaqs' => $faqs->groupBy('category'),
            'faqCount' => $faqs->count(),
        ]);
    }

    /** Runs the rule-based search and shows the strongest matching FAQ (POST /help). */
    public function search(Request $request)
    {
        $data = $request->validate([
            'question' => ['required', 'string', 'max:500'],
        ]);

        $faqs = $this->faqList();
        $input = $this->normalise($data['question']);
        $tokens = $this->searchTokens($input);

        $scored = $faqs
            ->map(function (Faq $faq) use ($input, $tokens) {
                return [
                    'faq' => $faq,
                    'score' => $this->scoreFaq($faq, $input, $tokens),
                ];
            })
            ->filter(fn (array $row) => $row['score'] > 0)
            ->sortByDesc('score')
            ->values();

        $answer = $scored->isNotEmpty() ? $scored->first()['faq'] : null;

        return view('help.index', [
            'question' => $data['question'],
            'answer' => $answer,
            'allFaqs' => $faqs->groupBy('category'),
            'faqCount' => $faqs->count(),
        ]);
    }

    private function faqList(): Collection
    {
        return Faq::with('keywords')
            ->orderBy('category')
            ->orderBy('question')
            ->get();
    }

    private function scoreFaq(Faq $faq, string $input, Collection $tokens): int
    {
        $question = $this->normalise($this->translatedFaqValue($faq, 'question'));
        $answer = $this->normalise($this->translatedFaqValue($faq, 'answer'));
        $category = $this->normalise($this->translatedCategory($faq->category));
        $sourceQuestion = $this->normalise($faq->question);
        $sourceAnswer = $this->normalise($faq->answer);
        $keywords = $faq->keywords
            ->pluck('keyword')
            ->map(fn ($keyword) => $this->normalise($keyword));

        $score = 0;

        if ($input !== '') {
            if (str_contains($question, $input) || str_contains($sourceQuestion, $input)) {
                $score += 14;
            }
            if ($keywords->contains(fn ($keyword) => str_contains($keyword, $input) || str_contains($input, $keyword))) {
                $score += 12;
            }
            if (str_contains($answer, $input) || str_contains($sourceAnswer, $input)) {
                $score += 6;
            }
            if (str_contains($category, $input)) {
                $score += 4;
            }
        }

        foreach ($tokens as $token) {
            if (str_contains($question, $token) || str_contains($sourceQuestion, $token)) {
                $score += 5;
            }
            if ($keywords->contains(fn ($keyword) => str_contains($keyword, $token))) {
                $score += 6;
            }
            if (str_contains($answer, $token) || str_contains($sourceAnswer, $token)) {
                $score += 2;
            }
            if (str_contains($category, $token)) {
                $score += 2;
            }
        }

        return $score;
    }

    private function translatedFaqValue(Faq $faq, string $field): string
    {
        $key = "patient.help_center.faqs.{$faq->faq_id}.{$field}";
        $translated = __($key);

        return $translated === $key ? (string) $faq->{$field} : $translated;
    }

    private function translatedCategory(?string $category): string
    {
        $category = $category ?: 'General';
        $slug = Str::of($category)->lower()->replace([' ', '-'], '_')->toString();
        $key = "patient.help_center.categories.{$slug}";
        $translated = __($key);

        return $translated === $key ? $category : $translated;
    }

    private function normalise(?string $value): string
    {
        return trim(mb_strtolower((string) $value));
    }

    private function searchTokens(string $input): Collection
    {
        $stopWords = collect([
            'a', 'an', 'and', 'are', 'can', 'do', 'does', 'for', 'from', 'how', 'i', 'in',
            'is', 'it', 'my', 'of', 'on', 'the', 'to', 'what', 'where', 'who', 'with',
            'আমি', 'আমার', 'কি', 'কী', 'কিভাবে', 'কীভাবে', 'এবং', 'এর', 'এ', 'তে', 'থেকে',
        ]);

        return collect(preg_split('/[^\\p{L}\\p{N}]+/u', $input, -1, PREG_SPLIT_NO_EMPTY))
            ->filter(fn ($token) => mb_strlen($token) >= 2 && ! $stopWords->contains($token))
            ->unique()
            ->values();
    }
}

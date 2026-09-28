<?php

namespace App\Services;

use App\Models\MedicineInfoDraft;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Looks up a generic medicine on openFDA (free, no API key) and saves what it
 * finds as a DRAFT for an admin to approve — see MedicineInfoDraft.
 *
 * Never called while a patient is chatting. Two reasons: the assistant must
 * keep working with no internet, and openFDA returns US drug-label prose
 * written for a different market, which a person should vet before a patient
 * reads it.
 *
 * Known limits, by design rather than oversight:
 *   - US labels use different names for the same drug, so INN/BD names are
 *     mapped below ("paracetamol" finds nothing; "acetaminophen" does).
 *   - Products with no US equivalent (oral rehydration salt) simply return
 *     nothing, and the entry stays for a human to write.
 *   - English only. Bangla has to be written or translated by a person.
 */
class MedicineInfoFetcher
{
    private const ENDPOINT = 'https://api.fda.gov/drug/label.json';

    /** INN / Bangladeshi spellings → the name US labels use. */
    private const US_NAMES = [
        'paracetamol' => 'acetaminophen',
        'salbutamol' => 'albuterol',
        'adrenaline' => 'epinephrine',
        'noradrenaline' => 'norepinephrine',
        'frusemide' => 'furosemide',
        'lignocaine' => 'lidocaine',
        'rifampicin' => 'rifampin',
        'amoxycillin' => 'amoxicillin',
        'cotrimoxazole' => 'sulfamethoxazole and trimethoprim',
        'glyceryl trinitrate' => 'nitroglycerin',
        'metamizole' => 'dipyrone',
        'pethidine' => 'meperidine',
    ];

    /**
     * Label sections worth showing a patient, in the order we prefer them.
     * Over-the-counter labels use "do not use" / "ask a doctor"; prescription
     * labels use "boxed warning" / "contraindications" instead, so both sets
     * are listed or prescription medicines come back with no cautions at all.
     */
    private const CAUTION_FIELDS = [
        'boxed_warning', 'do_not_use', 'contraindications', 'warnings_and_cautions',
        'ask_doctor', 'ask_doctor_or_pharmacist', 'stop_use', 'pregnancy_or_breast_feeding', 'warnings',
    ];

    public function __construct(private DrugClassGuesser $guesser)
    {
    }

    /** Fetches and stores a pending draft, or returns null when nothing usable came back. */
    public function fetchAndStore(string $genericName): ?MedicineInfoDraft
    {
        $found = $this->fetch($genericName);

        if ($found === null) {
            return null;
        }

        return MedicineInfoDraft::create($found + [
            'generic_name' => mb_strtolower(trim($genericName)),
            'status' => 'pending',
        ]);
    }

    /** @return array{queried_as: string, source: string, source_ref: ?string, uses_en: string, cautions_en: array, suggested_prescription_only: bool}|null */
    public function fetch(string $genericName): ?array
    {
        $key = mb_strtolower(trim($genericName));
        $queryName = self::US_NAMES[$key] ?? $key;

        try {
            $response = Http::timeout(12)->retry(2, 500)->get(self::ENDPOINT, [
                'search' => 'openfda.generic_name:"' . $queryName . '"',
                // Several labels match a common generic — ask for a handful so
                // pickBestResult() can skip combination products.
                'limit' => 5,
            ]);
        } catch (\Throwable $e) {
            // Offline or the API is down — the caller reports it and the
            // existing offline information is left untouched.
            Log::info("medicine info fetch failed for {$genericName}: " . $e->getMessage());

            return null;
        }

        if (!$response->successful() || !isset($response->json()['results'][0])) {
            return null;
        }

        $result = $this->pickBestResult($response->json()['results'], $queryName);

        if ($result === null) {
            return null;
        }

        $uses = $this->clean($result['indications_and_usage'] ?? $result['purpose'] ?? null, 400);

        if ($uses === null) {
            return null;
        }

        $cautions = [];
        foreach (self::CAUTION_FIELDS as $field) {
            if (count($cautions) >= 3) {
                break;
            }
            if ($text = $this->clean($result[$field] ?? null, 220)) {
                $cautions[] = $text;
            }
        }

        $productType = $result['openfda']['product_type'][0] ?? '';
        $guess = $this->guesser->guess($key);

        return [
            'queried_as' => $queryName,
            'source' => 'openfda',
            'source_ref' => $result['set_id'] ?? null,
            'uses_en' => $uses,
            'cautions_en' => $cautions,
            'suggested_prescription_only' => str_contains(mb_strtolower($productType), 'prescription')
                ?: (bool) ($guess['is_prescription_only'] ?? false),
        ];
    }

    /**
     * A search for "metformin" also matches combination products such as
     * sitagliptin+metformin, whose label describes the combination rather than
     * the medicine asked about. Prefer a label whose generic name IS the drug
     * we asked for, and a single-ingredient one over a combination.
     */
    private function pickBestResult(array $results, string $queryName): ?array
    {
        $best = null;
        $bestScore = 0;

        foreach ($results as $result) {
            $score = $this->scoreResult($result, $queryName);
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $result;
            }
        }

        // Nothing scored high enough to be confident it IS this drug — better
        // to report nothing than to describe a different medicine.
        return $bestScore >= 3 ? $best : null;
    }

    /** Salt suffixes are part of the formal name, not a different drug: "metformin hydrochloride" is metformin. */
    private const SALT_WORDS = ['hydrochloride', 'hcl', 'sodium', 'potassium', 'calcium', 'sulfate', 'sulphate', 'maleate', 'tartrate', 'besylate', 'mesylate', 'citrate', 'phosphate', 'succinate', 'fumarate', 'acetate', 'nitrate', 'dihydrate', 'monohydrate', 'anhydrous'];

    private function scoreResult(array $result, string $queryName): int
    {
        $names = array_map(fn ($n) => mb_strtolower(trim($n)), $result['openfda']['generic_name'] ?? []);

        if ($names === []) {
            return 0;
        }

        // A combination product ("sitagliptin and metformin hydrochloride")
        // has its own label describing the combination, not the single drug
        // the patient asked about.
        $isCombination = count($names) > 1 || preg_grep('/\band\b|,|\//u', $names);
        $score = $isCombination ? 0 : 2;

        foreach ($names as $name) {
            $stripped = preg_replace('/\s+/u', ' ', trim(preg_replace('/\b(' . implode('|', self::SALT_WORDS) . ')\b/u', '', $name)));

            if ($stripped === $queryName) {
                return $score + 3;
            }
            if (str_contains($stripped, $queryName)) {
                $score += 1;
            }
        }

        return $score;
    }

    /** Label text arrives as an array of long strings full of bullets and headings — tidy it into something readable. */
    private function clean(array|string|null $value, int $limit): ?string
    {
        $text = is_array($value) ? ($value[0] ?? null) : $value;

        if (!is_string($text) || trim($text) === '') {
            return null;
        }

        $text = preg_replace('/\s+/u', ' ', str_replace(['•', '·', '●'], ' ', $text));
        // Labels start with their own section number and heading, e.g.
        // "1 INDICATIONS AND USAGE ..." or "Warnings:".
        $text = preg_replace('/^\d+(\.\d+)*\s*/u', '', trim($text));
        $text = preg_replace(
            // Labels write it either way: "INDICATIONS AND USAGE" / "INDICATIONS & USAGE".
            '/^(indications? (and|&) usage|dosage (and|&) administration|warnings (and|&) cautions|contraindications|boxed warning|uses?|warnings?|purpose|do not use|ask (a )?doctor( or pharmacist)?|stop use)\s*:?\s*/iu',
            '',
            $text
        );
        $text = trim($text);

        if (mb_strlen($text) > $limit) {
            $cut = mb_substr($text, 0, $limit);
            $lastStop = mb_strrpos($cut, '. ');
            $text = ($lastStop !== false && $lastStop > $limit * 0.5 ? mb_substr($cut, 0, $lastStop + 1) : rtrim($cut)) . '…';
        }

        return $text === '' ? null : $text;
    }
}

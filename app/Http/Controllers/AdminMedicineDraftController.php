<?php

namespace App\Http\Controllers;

use App\Models\MedicineGenericInfo;
use App\Models\MedicineInfoDraft;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Admin review of medicine information fetched from openFDA
 * (MedicineInfoFetcher). A draft only reaches patients when an admin
 * approves it here — and the text can be edited first, which matters because
 * the source is a US drug label written for a different market.
 */
class AdminMedicineDraftController extends Controller
{
    public function index(Request $request)
    {
        $status = in_array($request->get('status'), ['pending', 'approved', 'rejected'], true)
            ? $request->get('status')
            : 'pending';

        $drafts = MedicineInfoDraft::where('status', $status)
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        $counts = MedicineInfoDraft::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $needingInfo = MedicineGenericInfo::where('needs_review', true)->count();

        return view('admin.medicine-drafts', compact('drafts', 'status', 'counts', 'needingInfo'));
    }

    /** Publish the (possibly edited) text to the generic every brand shares. */
    public function approve(Request $request, MedicineInfoDraft $draft)
    {
        $data = $request->validate([
            'uses_en' => ['required', 'string', 'max:2000'],
            'cautions_en' => ['nullable', 'string', 'max:3000'],
            'is_prescription_only' => ['nullable', 'boolean'],
        ]);

        $cautions = collect(preg_split('/\r\n|\r|\n/', $data['cautions_en'] ?? ''))
            ->map(fn ($line) => trim($line))
            ->filter()
            ->values()
            ->all();

        MedicineGenericInfo::updateOrCreate(
            ['generic_name' => $draft->generic_name],
            [
                'uses_en' => $data['uses_en'],
                'cautions_en' => $cautions,
                'is_prescription_only' => (bool) ($data['is_prescription_only'] ?? false),
                // A human has now read it, so it counts as curated.
                'source' => 'curated',
                'needs_review' => false,
            ]
        );

        $draft->update(['status' => 'approved', 'reviewed_by' => Auth::id(), 'reviewed_at' => now()]);

        return back()->with('success', "Approved — patients asking about {$draft->generic_name} will now get this answer.");
    }

    public function reject(MedicineInfoDraft $draft)
    {
        $draft->update(['status' => 'rejected', 'reviewed_by' => Auth::id(), 'reviewed_at' => now()]);

        return back()->with('success', 'Rejected — nothing was published to patients.');
    }
}

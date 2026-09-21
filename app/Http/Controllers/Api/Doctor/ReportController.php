<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ReportController extends Controller
{
    public function index()
    {
        $reports = Report::where('reporter_account_id', Auth::id())
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'stats' => [
                'open' => $reports->where('status', 'open')->count(),
                'in_review' => $reports->where('status', 'in_review')->count(),
                'resolved' => $reports->where('status', 'resolved')->count(),
                'total' => $reports->count(),
            ],
            'reports' => $reports
                ->map(fn (Report $report) => [
                    'report_id' => (int) $report->report_id,
                    'subject' => $report->subject,
                    'description' => $report->description,
                    'status' => $report->status,
                    'status_label' => Str::headline($report->status),
                    'admin_response' => $report->admin_response,
                    'created_at' => $report->created_at?->toIso8601String(),
                    'created_label' => $report->created_at?->format('D, M j Y g:i A'),
                    'resolved_at' => $report->resolved_at?->toIso8601String(),
                ])
                ->values(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:190'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        $report = Report::create([
            'reporter_account_id' => Auth::id(),
            'subject' => trim($data['subject']),
            'description' => trim($data['description']),
            'status' => 'open',
        ]);

        return response()->json([
            'message' => 'Your report has been submitted. Admin will review it soon.',
            'report_id' => (int) $report->report_id,
        ], 201);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Report;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Any logged-in user — any of the 5 non-admin roles — can file a bug or
 * system report here. Admin reviews and responds to these in the admin
 * console (see AdminController::reports()/respondToReport()).
 */
class ReportController extends Controller
{
    /** Shows this account's own past reports (GET /report). */
    public function index()
    {
        $reports = Report::where('reporter_account_id', Auth::id())
            ->orderByDesc('created_at')
            ->get();

        return view('report.index', compact('reports'));
    }

    /** Shows the "submit a report" form (GET /report/create). */
    public function create()
    {
        return view('report.create');
    }

    /** Submits a new report (POST /report). */
    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:190'],
            'description' => ['required', 'string', 'max:2000'],
        ]);

        Report::create([
            'reporter_account_id' => Auth::id(),
            'subject' => $data['subject'],
            'description' => $data['description'],
            'status' => 'open',
        ]);

        return redirect()->route('report.index')->with('success', 'Your report has been submitted. Admin will review it soon.');
    }
}

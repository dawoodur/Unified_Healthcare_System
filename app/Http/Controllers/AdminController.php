<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\ConsultationChatArchive;
use App\Models\ConsultationChatArchiveMessage;
use App\Models\DoctorCertificate;
use App\Models\Payment;
use App\Models\Report;
use App\Services\AppointmentService;
use App\Services\ConsultationChatService;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/**
 * Every account already has a unique, permanent ID the moment it's
 * created — `account_id` on the `accounts` table — that's the "u_id"
 * here. No new column needed: all 6 roles (patient, doctor, hospital,
 * pharmacy, delivery, admin) share that one `accounts` table, so every
 * single user across the whole platform already has exactly one such ID,
 * and it already works as one shared ID space, not six separate ones.
 */
class AdminController extends Controller
{
    public function __construct(
        private AppointmentService $appointments,
        private NotificationService $notifications,
        private ConsultationChatService $consultationChat,
    ) {
    }

    /**
     * Shows every user (u_id, name, role, ...), optionally narrowed by name,
     * and — if a u_id is given — the full record for that one user
     * (GET /admin/users).
     */
    public function searchUser(Request $request)
    {
        $uid = $request->integer('u_id') ?: null;
        $name = trim((string) $request->get('name'));
        $account = null;
        $profile = null;
        $notFound = false;

        if ($uid) {
            $account = Account::find($uid);

            if ($account) {
                $profile = $account->profile(); // the role-specific row — see Account::profile()
            } else {
                $notFound = true;
            }
        }

        // Eager-load every possible profile relation up front so
        // displayName() below doesn't fire one query per row (N+1).
        $accounts = Account::with(['patient', 'doctor', 'hospital', 'pharmacy', 'deliveryAgent', 'admin'])
            ->orderBy('account_id')
            ->get();

        if ($name !== '') {
            $needle = mb_strtolower($name);
            $accounts = $accounts->filter(
                fn ($acc) => str_contains(mb_strtolower($acc->displayName()), $needle)
            );
        }

        return view('admin.users', compact('uid', 'name', 'account', 'profile', 'notFound', 'accounts'));
    }

    /** Lists every money transaction on the platform, most recent first (GET /admin/transactions). */
    public function transactions(Request $request)
    {
        $this->appointments->expireNoShows(); // so a just-refunded no-show payment shows up as such here too

        $status = $request->get('status') ?: null;

        $payments = Payment::with([
                'account',
                'paymentMethod',
                'appointment.patient',
                'appointment.doctor',
                'medicineOrder.patient',
                'medicineOrder.pharmacy',
            ])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->get();

        $totalCompleted = $payments->where('status', 'completed')->sum('amount');

        return view('admin.transactions', compact('payments', 'status', 'totalCompleted'));
    }

    /** Lists doctor certificates for admin to approve/reject (GET /admin/doctor-verifications). */
    public function doctorVerifications()
    {
        $certificates = DoctorCertificate::with('doctor.account')->orderByDesc('uploaded_at')->get();
        $pending = $certificates->where('verification_status', 'pending');
        $reviewed = $certificates->where('verification_status', '!=', 'pending');

        return view('admin.doctor-verifications', compact('pending', 'reviewed'));
    }

    /** Approves a doctor's certificate, unlocking their visibility in patient search (POST). */
    public function approveCertificate(DoctorCertificate $certificate)
    {
        $adminId = Auth::user()->profile()->admin_id;

        $certificate->update([
            'verification_status' => 'approved',
            'reviewed_by_admin_id' => $adminId,
            'reviewed_at' => now(),
        ]);

        $certificate->doctor->update(['verification_status' => 'approved']);

        return back()->with('success', "Dr. {$certificate->doctor->full_name}'s certificate approved. They're now visible to patients.");
    }

    /** Rejects a doctor's certificate (POST). */
    public function rejectCertificate(DoctorCertificate $certificate)
    {
        $adminId = Auth::user()->profile()->admin_id;

        $certificate->update([
            'verification_status' => 'rejected',
            'reviewed_by_admin_id' => $adminId,
            'reviewed_at' => now(),
        ]);

        $certificate->doctor->update(['verification_status' => 'rejected']);

        return back()->with('success', "Dr. {$certificate->doctor->full_name}'s certificate rejected.");
    }

    /** Streams a doctor's uploaded certificate file so admin can inspect it before deciding (GET). */
    public function downloadCertificate(DoctorCertificate $certificate)
    {
        if (! Storage::disk('local')->exists($certificate->file_path)) {
            abort(404, 'Certificate file not found.');
        }

        return Storage::disk('local')->download($certificate->file_path);
    }

    /**
     * Streams one photo shared during an already-ended video consultation,
     * from the admin-only 7-day archive (GET
     * /admin/consultation-chat-history/{archive}/photos/{message}) — see
     * ConsultationChatService::archiveForAdmin(). Same "still inside the
     * retention window" guard as AdminPanelController::consultationChatHistoryShow().
     */
    public function showConsultationChatPhoto(ConsultationChatArchive $archive, ConsultationChatArchiveMessage $message)
    {
        if ($archive->archived_at->lt(now()->subDays(7))) {
            abort(404);
        }

        $path = $this->consultationChat->archivedPhotoPath($archive, $message);
        if (! $path) {
            abort(404);
        }

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'no-store, private']);
    }

    /** Lists bug/system reports submitted by any of the 5 non-admin roles (GET /admin/reports). */
    public function reports(Request $request)
    {
        $status = $request->get('status') ?: null;

        $reports = Report::with('reporter')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->get();

        return view('admin.reports', compact('reports', 'status'));
    }

    /** Admin responds to a report and updates its status (POST /admin/reports/{report}/respond). */
    public function respondToReport(Request $request, Report $report)
    {
        $data = $request->validate([
            'status' => ['required', Rule::in(['in_review', 'resolved', 'dismissed'])],
            'admin_response' => ['nullable', 'string', 'max:2000'],
        ]);

        $report->update([
            'status' => $data['status'],
            'admin_response' => $data['admin_response'] ?? $report->admin_response,
            'resolved_at' => in_array($data['status'], ['resolved', 'dismissed'], true) ? now() : null,
        ]);

        $statusLabel = str_replace('_', ' ', $data['status']);
        $message = "Your report \"{$report->subject}\" was marked {$statusLabel}.";
        if (!empty($data['admin_response'])) {
            $message .= " Admin response: {$data['admin_response']}";
        }
        $this->notifications->notify($report->reporter, 'report_resolved', $message, $report->report_id);

        return back()->with('success', 'Report updated.');
    }
}

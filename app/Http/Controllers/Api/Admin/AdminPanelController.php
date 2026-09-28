<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Appointment;
use App\Models\ConsultationChatArchive;
use App\Models\Conversation;
use App\Models\Doctor;
use App\Models\DoctorCertificate;
use App\Models\MedicineGenericInfo;
use App\Models\MedicineInfoDraft;
use App\Models\MedicineOrder;
use App\Models\MedicineOrderItem;
use App\Models\Payment;
use App\Models\Prescription;
use App\Models\Report;
use App\Services\AppointmentService;
use App\Support\MonthlySeries;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * JSON twin of the admin console — the eight screens under /admin.
 *
 * Read-only by design, exactly like the other five sections' API layers.
 * Approving a certificate, answering a report, and approving a medicine
 * draft all stay on the existing web routes (AdminController /
 * AdminMedicineDraftController), which already own those side effects and
 * their notifications; the React pages post plain forms to them rather
 * than duplicating that logic.
 */
class AdminPanelController extends Controller
{
    public function __construct(private AppointmentService $appointments)
    {
    }

    public function translations()
    {
        return response()->json([
            'locale' => App::getLocale(),
            'dashboard' => __('dashboard.admin'),
        ]);
    }

    public function dashboard()
    {
        $admin = Auth::user()->admin;

        $counts = [
            'patients' => DB::table('patients')->count(),
            'doctors' => Doctor::count(),
            'hospitals' => DB::table('hospitals')->count(),
            'pharmacies' => DB::table('pharmacies')->count(),
            'delivery_agents' => DB::table('delivery_agents')->count(),
            'pending_doctor_verifications' => Doctor::where('verification_status', 'pending')->count(),
        ];

        $sinceDate = now()->startOfMonth()->subMonths(5)->toDateString();

        $registrationsByMonth = MonthlySeries::fill(
            Account::where('created_at', '>=', $sinceDate)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as cnt")
                ->groupBy('ym')->pluck('cnt', 'ym'),
            fn ($v) => (string) (int) $v
        );
        $appointmentsByMonth = MonthlySeries::fill(
            Appointment::where('created_at', '>=', $sinceDate)
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as cnt")
                ->groupBy('ym')->pluck('cnt', 'ym'),
            fn ($v) => (string) (int) $v
        );
        $revenueByMonth = MonthlySeries::fill(
            Payment::where('status', 'completed')
                ->where('paid_at', '>=', $sinceDate)
                ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as ym, SUM(amount) as total")
                ->groupBy('ym')->pluck('total', 'ym'),
            fn ($v) => 'BDT ' . number_format((float) $v, 0)
        );

        $recentActivity = collect()
            ->concat(
                Account::latest('created_at')->take(5)->get()
                    ->map(fn (Account $acc) => [
                        'message' => __('dashboard.admin.activity_registered', [
                            'role' => __('register.' . $acc->role) !== 'register.' . $acc->role ? __('register.' . $acc->role) : ucfirst($acc->role),
                            'name' => $acc->displayName(),
                        ]),
                        'time' => $acc->created_at,
                        'icon' => 'bi-person-plus',
                    ])
            )
            ->concat(
                Payment::where('status', 'completed')->with('account')->latest('paid_at')->take(5)->get()
                    ->map(fn (Payment $p) => [
                        'message' => __('dashboard.admin.activity_payment', ['amount' => number_format($p->amount, 0), 'name' => $p->account->displayName()]),
                        'time' => $p->paid_at ?? $p->created_at,
                        'icon' => 'bi-cash-coin',
                    ])
            )
            ->sortByDesc('time')->take(6)->values()
            ->map(fn (array $item) => [
                'message' => $item['message'],
                'icon' => $item['icon'],
                'when' => $item['time']?->diffForHumans(),
            ]);

        // Every figure here is a real, live-checked signal, not a placeholder:
        // the DB connection is actually pinged, disk space is actually read off
        // the server's storage volume, and failed_jobs is Laravel's own table.
        $dbOk = true;
        try {
            DB::connection()->getPdo();
        } catch (\Throwable $e) {
            $dbOk = false;
        }
        $dbSizeMb = (float) (DB::selectOne(
            'SELECT ROUND(SUM(data_length + index_length) / 1024 / 1024, 1) as size_mb FROM information_schema.tables WHERE table_schema = DATABASE()'
        )->size_mb ?? 0);

        $storageTotal = @disk_total_space(storage_path()) ?: 0;
        $storageFree = @disk_free_space(storage_path()) ?: 0;

        return response()->json([
            'admin' => [
                'full_name' => $admin->full_name,
                'uid_tag' => Auth::user()->uidTag(),
                'email' => Auth::user()->email,
            ],
            'counts' => array_map('intval', $counts),
            'charts' => [
                'registrations' => $this->series($registrationsByMonth),
                'appointments' => $this->series($appointmentsByMonth),
                'revenue' => $this->series($revenueByMonth),
            ],
            'recent_activity' => $recentActivity,
            'system_health' => [
                'db_ok' => $dbOk,
                'db_size_mb' => $dbSizeMb,
                'storage_used_percent' => $storageTotal > 0 ? (int) round((($storageTotal - $storageFree) / $storageTotal) * 100) : 0,
                'storage_free_gb' => round($storageFree / 1024 / 1024 / 1024, 1),
                'failed_jobs' => DB::table('failed_jobs')->count(),
            ],
        ]);
    }

    /**
     * Every account, optionally narrowed by name, plus the full record for one
     * u_id. Mirrors AdminController::searchUser() — the u_id is the
     * account_id every one of the six roles already shares.
     */
    public function users(Request $request)
    {
        $uid = $request->integer('u_id') ?: null;
        $name = trim((string) $request->get('name'));

        $account = $uid ? Account::find($uid) : null;

        // Eager-load every possible profile relation up front so displayName()
        // below does not fire one query per row (N+1).
        $accounts = Account::with(['patient', 'doctor', 'hospital', 'pharmacy', 'deliveryAgent', 'admin'])
            ->orderBy('account_id')
            ->get();

        if ($name !== '') {
            $needle = mb_strtolower($name);
            $accounts = $accounts->filter(fn ($acc) => str_contains(mb_strtolower($acc->displayName()), $needle));
        }

        return response()->json([
            'query' => ['u_id' => $uid, 'name' => $name],
            'not_found' => $uid !== null && $account === null,
            'account' => $account ? $this->accountDetail($account) : null,
            'accounts' => $accounts->values()->map(fn (Account $acc) => [
                'account_id' => (int) $acc->account_id,
                'uid_tag' => $acc->uidTag(),
                'name' => $acc->displayName(),
                'role' => $acc->role,
                'email' => $acc->email,
                'is_verified' => (bool) $acc->is_verified,
                'is_active' => (bool) $acc->is_active,
                'joined_label' => $acc->created_at?->format('M j, Y'),
            ]),
        ]);
    }

    /** Every payment on the platform, newest first (GET /api/admin/transactions). */
    public function transactions(Request $request)
    {
        // So a just-refunded no-show payment shows up as such here too.
        $this->appointments->expireNoShows();

        $status = $request->get('status') ?: null;

        $payments = Payment::with([
                'account', 'paymentMethod',
                'appointment.patient', 'appointment.doctor',
                'medicineOrder.patient', 'medicineOrder.pharmacy',
            ])
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'status' => $status,
            'total_completed' => (float) $payments->where('status', 'completed')->sum('amount'),
            'counts' => Payment::selectRaw('status, COUNT(*) as cnt')->groupBy('status')->pluck('cnt', 'status'),
            'payments' => $payments->map(fn (Payment $p) => [
                'payment_id' => (int) $p->payment_id,
                'payer' => $p->account?->uidTag() ?? '#u' . $p->account_id,
                'payer_name' => $p->account?->displayName(),
                'for' => $this->paymentSubject($p),
                'amount' => (float) $p->amount,
                'method' => $p->paymentMethod->method_name ?? null,
                'status' => $p->status,
                'status_label' => ucfirst($p->status),
                'paid_at_label' => $p->paid_at?->format('M j, Y g:i A'),
            ]),
        ]);
    }

    /** Doctor certificates waiting on a decision, and the ones already decided. */
    public function doctorVerifications()
    {
        $certificates = DoctorCertificate::with('doctor.account')->orderByDesc('uploaded_at')->get();

        $shape = fn (DoctorCertificate $c) => [
            'certificate_id' => (int) $c->getKey(),
            'doctor_name' => $c->doctor->full_name ?? null,
            'doctor_uid' => $c->doctor->account?->uidTag(),
            'uploaded_label' => $c->uploaded_at?->format('M j, Y g:i A'),
            'status' => $c->verification_status,
            'status_label' => ucfirst($c->verification_status),
            'reviewed_label' => $c->reviewed_at?->format('M j, Y g:i A'),
        ];

        return response()->json([
            'pending' => $certificates->where('verification_status', 'pending')->values()->map($shape),
            'reviewed' => $certificates->where('verification_status', '!=', 'pending')->values()->map($shape),
        ]);
    }

    /** Bug/system reports submitted by the five non-admin roles. */
    public function reports(Request $request)
    {
        $status = $request->get('status') ?: null;

        $reports = Report::with('reporter')
            ->when($status, fn ($query) => $query->where('status', $status))
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'status' => $status,
            'counts' => Report::selectRaw('status, COUNT(*) as cnt')->groupBy('status')->pluck('cnt', 'status'),
            'reports' => $reports->map(fn (Report $r) => [
                'report_id' => (int) $r->report_id,
                'subject' => $r->subject,
                'description' => $r->description,
                'status' => $r->status,
                'status_label' => ucfirst(str_replace('_', ' ', $r->status)),
                'admin_response' => $r->admin_response,
                'reporter' => $r->reporter?->uidTag() ?? '#u' . $r->reporter_account_id,
                'reporter_role' => $r->reporter?->role,
                'created_label' => $r->created_at?->format('M j, Y g:i A'),
            ]),
        ]);
    }

    /** Revenue, registrations and the busiest corners of the platform. */
    public function analytics()
    {
        $revenueByMonth = MonthlySeries::fill(
            Payment::where('status', 'completed')
                ->where('paid_at', '>=', now()->startOfMonth()->subMonths(5))
                ->selectRaw("DATE_FORMAT(paid_at, '%Y-%m') as ym, SUM(amount) as total")
                ->groupBy('ym')->pluck('total', 'ym'),
            fn ($value) => 'BDT ' . number_format((float) $value, 0)
        );

        $registrationsByMonth = MonthlySeries::fill(
            Account::where('created_at', '>=', now()->startOfMonth()->subMonths(5))
                ->selectRaw("DATE_FORMAT(created_at, '%Y-%m') as ym, COUNT(*) as cnt")
                ->groupBy('ym')->pluck('cnt', 'ym'),
            fn ($value) => (string) $value
        );

        $busiestDoctors = Doctor::withCount('appointments')
            ->orderByDesc('appointments_count')
            ->take(5)->get()
            ->map(fn (Doctor $d) => ['label' => 'Dr. ' . $d->full_name, 'value' => (int) $d->appointments_count, 'formatted' => (string) $d->appointments_count]);

        $topMedicines = MedicineOrderItem::selectRaw('medicine_master_id, SUM(quantity) as total_qty')
            ->groupBy('medicine_master_id')
            ->orderByDesc('total_qty')
            ->take(5)->with('medicine')->get()
            ->map(fn (MedicineOrderItem $row) => ['label' => $row->medicine->generic_name, 'value' => (int) $row->total_qty, 'formatted' => $row->total_qty . ' units']);

        $orderStatusCounts = MedicineOrder::selectRaw('status, COUNT(*) as cnt')
            ->groupBy('status')->get()
            ->map(fn ($row) => ['label' => ucfirst(str_replace('_', ' ', $row->status)), 'value' => (int) $row->cnt, 'formatted' => (string) $row->cnt]);

        return response()->json([
            'charts' => [
                ['key' => 'revenue', 'title' => 'Revenue (completed payments)', 'icon' => 'bi-cash-coin', 'tone' => 'green', 'rows' => $this->series($revenueByMonth)],
                ['key' => 'registrations', 'title' => 'Registrations per month', 'icon' => 'bi-person-plus', 'tone' => 'indigo', 'rows' => $this->series($registrationsByMonth)],
                ['key' => 'doctors', 'title' => 'Busiest doctors', 'icon' => 'bi-person-badge', 'tone' => 'blue', 'rows' => $busiestDoctors],
                ['key' => 'medicines', 'title' => 'Most ordered medicines', 'icon' => 'bi-capsule', 'tone' => 'teal', 'rows' => $topMedicines],
                ['key' => 'orders', 'title' => 'Orders by status', 'icon' => 'bi-bag-check', 'tone' => 'amber', 'rows' => $orderStatusCounts],
            ],
        ]);
    }

    /** openFDA medicine information waiting on a human before patients see it. */
    public function medicineDrafts(Request $request)
    {
        $status = in_array($request->get('status'), ['pending', 'approved', 'rejected'], true)
            ? $request->get('status')
            : 'pending';

        $drafts = MedicineInfoDraft::where('status', $status)
            ->orderByDesc('created_at')
            ->paginate(15);

        $counts = MedicineInfoDraft::selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');

        return response()->json([
            'status' => $status,
            'counts' => collect(['pending', 'approved', 'rejected'])
                ->mapWithKeys(fn ($key) => [$key => (int) ($counts[$key] ?? 0)]),
            'needing_info' => MedicineGenericInfo::where('needs_review', true)->count(),
            'drafts' => collect($drafts->items())->map(fn (MedicineInfoDraft $d) => [
                'draft_id' => (int) $d->draft_id,
                'generic_name' => ucfirst($d->generic_name),
                'source' => $d->source,
                // Only worth showing when the fetcher had to search under a
                // different name than the one it filed the draft under.
                'queried_as' => $d->queried_as !== $d->generic_name ? $d->queried_as : null,
                'source_url' => $d->sourceUrl(),
                'created_label' => $d->created_at?->format('M j, Y'),
                'uses_en' => $d->uses_en,
                'cautions_en' => $d->cautions_en ?? [],
                'cautions_text' => implode("\n", $d->cautions_en ?? []),
                'suggested_prescription_only' => (bool) $d->suggested_prescription_only,
                'status' => $d->status,
                'reviewed_label' => $d->reviewed_at?->format('M j, Y'),
            ]),
            'pagination' => $this->pagination($drafts),
        ]);
    }

    /** Patient-scanned prescriptions (PrescriptionScanController) waiting on sign-off before their medicines can be ordered — see Patient::unverifiedScannedMedicineIds(). */
    public function scannedPrescriptions(Request $request)
    {
        $status = in_array($request->get('status'), ['pending_review', 'verified', 'rejected'], true)
            ? $request->get('status')
            : 'pending_review';

        $prescriptions = Prescription::where('source', 'patient_scanned')
            ->where('verification_status', $status)
            ->with(['patient', 'items.medicine', 'facilityItems.facilityType.category', 'reviewer'])
            ->orderByDesc('issued_at')
            ->paginate(15);

        $counts = Prescription::where('source', 'patient_scanned')
            ->selectRaw('verification_status, COUNT(*) as total')
            ->groupBy('verification_status')
            ->pluck('total', 'verification_status');

        return response()->json([
            'status' => $status,
            'counts' => collect(['pending_review', 'verified', 'rejected'])
                ->mapWithKeys(fn ($key) => [$key => (int) ($counts[$key] ?? 0)]),
            'prescriptions' => collect($prescriptions->items())->map(fn (Prescription $p) => [
                'prescription_id' => (int) $p->prescription_id,
                'patient_name' => $p->patient->full_name,
                'scanned_patient_name' => $p->scanned_patient_name,
                'external_doctor_name' => $p->external_doctor_name,
                'external_hospital_name' => $p->external_hospital_name,
                'issued_label' => $p->issued_at?->format('M j, Y g:i A'),
                'diagnosis_notes' => $p->diagnosis_notes,
                'medicines' => $p->items->map(fn ($item) => $item->medicine
                    ? $item->medicine->generic_name . ($item->medicine->brand_name ? " ({$item->medicine->brand_name})" : '')
                    : '—'),
                'facility_items' => $p->facilityItems->map(fn ($item) => [
                    'name' => $item->facilityType->name ?? '—',
                    'category' => $item->facilityType->category->category_name ?? '—',
                ]),
                'document_url' => $p->medical_record_id ? route('records.download', $p->medical_record_id) : null,
                'status' => $p->verification_status,
                'reviewed_label' => $p->reviewed_at ? ($p->reviewer?->displayName() ?? 'an admin') . ' on ' . $p->reviewed_at->format('M j, Y') : null,
            ]),
            'pagination' => $this->pagination($prescriptions),
        ]);
    }

    /**
     * Read-only history of inbox conversations InboxService archived once
     * the booking/order they existed for was done — only within the
     * 7-day window before PurgeArchivedInboxConversationsCommand deletes
     * them for good. A live (unarchived) conversation never shows up
     * here: admin's inbox oversight is "review what already ended," not
     * monitoring ongoing chats (see InboxController's docblock).
     */
    public function inboxHistory(Request $request)
    {
        $conversations = Conversation::whereNotNull('archived_at')
            ->where('archived_at', '>=', now()->subDays(7))
            ->with(['participantLow', 'participantHigh', 'messages'])
            ->orderByDesc('archived_at')
            ->paginate(15);

        return response()->json([
            'conversations' => collect($conversations->items())->map(function (Conversation $c) {
                $lastMessage = $c->messages->sortByDesc('message_id')->first();

                return [
                    'conversation_id' => (int) $c->conversation_id,
                    'participants' => [
                        $this->conversationParticipantCard($c->participantLow),
                        $this->conversationParticipantCard($c->participantHigh),
                    ],
                    'message_count' => $c->messages->count(),
                    'last_message_preview' => $lastMessage ? Str::limit($lastMessage->message_text, 80) : null,
                    'archived_label' => $c->archived_at?->format('M j, Y g:i A'),
                    'purge_in_days' => max(0, 7 - (int) floor($c->archived_at->diffInHours(now()) / 24)),
                ];
            }),
            'pagination' => $this->pagination($conversations),
        ]);
    }

    /** One archived conversation's full message thread — see inboxHistory() above for the retention window this is scoped to. */
    public function inboxHistoryShow(Conversation $conversation)
    {
        if ($conversation->archived_at === null || $conversation->archived_at->lt(now()->subDays(7))) {
            abort(404);
        }

        $conversation->load(['participantLow', 'participantHigh']);

        return response()->json([
            'conversation_id' => (int) $conversation->conversation_id,
            'participants' => [
                $this->conversationParticipantCard($conversation->participantLow),
                $this->conversationParticipantCard($conversation->participantHigh),
            ],
            'archived_label' => $conversation->archived_at?->format('M j, Y g:i A'),
            'messages' => $conversation->messages()->orderBy('message_id')->get(['message_id', 'sender_account_id', 'message_text', 'sent_at'])->map(fn ($m) => [
                'message_id' => (int) $m->message_id,
                'sender_account_id' => (int) $m->sender_account_id,
                'sender_is_low' => (int) $m->sender_account_id === (int) $conversation->participant_low_id,
                'message_text' => $m->message_text,
                'sent_at' => $m->sent_at?->format('M j, Y g:i A'),
            ]),
        ]);
    }

    private function conversationParticipantCard(Account $account): array
    {
        return [
            'account_id' => (int) $account->account_id,
            'name' => $account->displayName(),
            'role' => $account->role,
            'role_label' => ucfirst($account->role),
        ];
    }

    /**
     * Read-only history of video-consultation in-call chats — normally
     * destroyed the moment a call ends (see ConsultationChatService), but
     * snapshotted for admin review at that same moment
     * (ConsultationChatService::archiveForAdmin()) and kept for 7 days
     * before ConsultationChatArchivePurgeCommand deletes it. Same
     * retention shape as inboxHistory() above.
     */
    public function consultationChatHistory(Request $request)
    {
        $archives = ConsultationChatArchive::where('archived_at', '>=', now()->subDays(7))
            ->with(['patientAccount', 'doctorAccount', 'messages'])
            ->orderByDesc('archived_at')
            ->paginate(15);

        return response()->json([
            'archives' => collect($archives->items())->map(function (ConsultationChatArchive $archive) {
                $textCount = $archive->messages->where('type', 'text')->count();
                $photoCount = $archive->messages->where('type', 'photo')->count();

                return [
                    'archive_id' => (int) $archive->archive_id,
                    'appointment_id' => (int) $archive->appointment_id,
                    'patient' => $this->conversationParticipantCard($archive->patientAccount),
                    'doctor' => $this->conversationParticipantCard($archive->doctorAccount),
                    'text_count' => $textCount,
                    'photo_count' => $photoCount,
                    'archived_label' => $archive->archived_at?->format('M j, Y g:i A'),
                    'purge_in_days' => max(0, 7 - (int) floor($archive->archived_at->diffInHours(now()) / 24)),
                ];
            }),
            'pagination' => $this->pagination($archives),
        ]);
    }

    /** One archived consultation's full chat — see consultationChatHistory() above for the retention window this is scoped to. */
    public function consultationChatHistoryShow(ConsultationChatArchive $archive)
    {
        if ($archive->archived_at->lt(now()->subDays(7))) {
            abort(404);
        }

        $archive->load(['patientAccount', 'doctorAccount', 'messages']);

        return response()->json([
            'archive_id' => (int) $archive->archive_id,
            'patient' => $this->conversationParticipantCard($archive->patientAccount),
            'doctor' => $this->conversationParticipantCard($archive->doctorAccount),
            'archived_label' => $archive->archived_at?->format('M j, Y g:i A'),
            'messages' => $archive->messages->map(fn ($m) => [
                'archive_message_id' => (int) $m->archive_message_id,
                'sender_account_id' => (int) $m->sender_account_id,
                'sender_is_patient' => (int) $m->sender_account_id === (int) $archive->patient_account_id,
                'type' => $m->type,
                'message_text' => $m->message_text,
                'photo_url' => $m->isPhoto() ? route('admin.consultation-chat-history.photo', [$archive, $m]) : null,
                'sent_at_label' => $m->sent_at_label,
            ]),
        ]);
    }

    /** MonthlySeries hands back label/value/formatted rows; the charts want a plain list. */
    private function series($filled): array
    {
        return collect($filled)->values()->all();
    }

    private function pagination(LengthAwarePaginator $paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
        ];
    }

    private function paymentSubject(Payment $payment): array
    {
        if ($payment->appointment) {
            return [
                'title' => 'Appointment #' . $payment->appointment_id,
                'detail' => ($payment->appointment->patient->full_name ?? '—')
                    . ' → Dr. ' . ($payment->appointment->doctor->full_name ?? '—'),
            ];
        }

        if ($payment->medicineOrder) {
            return [
                'title' => 'Order #' . $payment->medicine_order_id,
                'detail' => ($payment->medicineOrder->patient->full_name ?? '—')
                    . ' → ' . ($payment->medicineOrder->pharmacy->pharmacy_name ?? '—'),
            ];
        }

        return ['title' => null, 'detail' => null];
    }

    /**
     * One account's full record: the shared account fields, then whatever the
     * role-specific profile row holds. The per-role shape mirrors the switch
     * the Blade page used, so nothing an admin could see before is missing.
     */
    private function accountDetail(Account $account): array
    {
        $profile = $account->profile();

        $detail = [
            'uid_tag' => $account->uidTag(),
            'role' => $account->role,
            'name' => $profile->full_name ?? $profile->hospital_name ?? $profile->pharmacy_name ?? null,
            'email' => $account->email,
            'mobile' => $account->mobile,
            'is_verified' => (bool) $account->is_verified,
            'is_active' => (bool) $account->is_active,
            'joined_label' => $account->created_at?->format('D, M j Y'),
            'last_login_label' => $account->last_login_at?->format('D, M j Y g:i A'),
            'fields' => [],
            'counts' => [],
            'note' => null,
        ];

        if (!$profile) {
            $detail['note'] = 'No profile row found for this account — this should not normally happen.';
            return $detail;
        }

        switch ($account->role) {
            case 'patient':
                $detail['fields'] = [
                    ['label' => 'Age', 'value' => $profile->age],
                    ['label' => 'Gender', 'value' => ucfirst($profile->gender)],
                    ['label' => 'Blood group', 'value' => $profile->blood_group],
                    ['label' => 'Address', 'value' => $profile->address],
                    ['label' => 'Reward points', 'value' => $profile->reward_points_balance],
                ];
                $detail['counts'] = [
                    ['label' => 'Appointments', 'value' => $profile->appointments()->count()],
                    ['label' => 'Facility bookings', 'value' => $profile->facilityBookings()->count()],
                    ['label' => 'Medicine orders', 'value' => $profile->medicineOrders()->count()],
                    ['label' => 'Medical records', 'value' => $profile->medicalRecords()->count()],
                ];
                break;

            case 'doctor':
                $detail['fields'] = [
                    ['label' => 'Age', 'value' => $profile->age],
                    ['label' => 'Gender', 'value' => ucfirst($profile->gender)],
                    ['label' => 'Blood group', 'value' => $profile->blood_group],
                    ['label' => 'Consultation fee', 'value' => 'BDT ' . number_format($profile->consultation_fee, 2)],
                    ['label' => 'Verification', 'value' => ucfirst($profile->verification_status), 'badge' => $profile->verification_status],
                    ['label' => 'Specialties', 'value' => $profile->specialties->pluck('specialty_name')->implode(', ') ?: 'None'],
                ];
                $detail['counts'] = [
                    ['label' => 'Appointments', 'value' => $profile->appointments()->count()],
                    ['label' => 'Prescriptions issued', 'value' => $profile->prescriptions()->count()],
                ];
                break;

            case 'hospital':
                $detail['fields'] = [
                    ['label' => 'Registration no.', 'value' => $profile->registration_number],
                    ['label' => 'Address', 'value' => $profile->fullAddress()],
                ];
                $detail['counts'] = [
                    ['label' => 'Assigned doctors', 'value' => $profile->activeDoctors()->count()],
                    ['label' => 'Priced facilities', 'value' => $profile->facilities()->count()],
                    ['label' => 'Onsite appointments', 'value' => $profile->appointments()->count()],
                    ['label' => 'Facility bookings', 'value' => $profile->facilityBookings()->count()],
                ];
                break;

            case 'pharmacy':
                $detail['fields'] = [
                    ['label' => 'ETIN', 'value' => $profile->etin_number],
                    ['label' => 'Address', 'value' => $profile->address],
                ];
                $detail['counts'] = [
                    ['label' => 'Stocked batches', 'value' => $profile->medicineStock()->count()],
                    ['label' => 'Orders received', 'value' => $profile->orders()->count()],
                ];
                break;

            case 'delivery':
                $detail['fields'] = [
                    ['label' => 'Age', 'value' => $profile->age],
                    ['label' => 'Gender', 'value' => ucfirst($profile->gender)],
                    ['label' => 'Blood group', 'value' => $profile->blood_group],
                ];
                $detail['counts'] = [
                    ['label' => 'Deliveries', 'value' => $profile->deliveries()->count()],
                ];
                break;

            case 'admin':
                $detail['note'] = 'No additional profile fields for an admin account.';
                break;
        }

        return $detail;
    }
}

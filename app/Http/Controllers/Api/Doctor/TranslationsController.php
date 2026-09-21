<?php

namespace App\Http\Controllers\Api\Doctor;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\App;

/**
 * Only the Dashboard page has real bilingual strings (lang/en|bn/dashboard.php's
 * 'doctor' => [...] section) — Appointments and Availability were already
 * hardcoded English in the original Blade views (zero __() calls), so the
 * React ports keep them hardcoded English too rather than inventing
 * bilingual support that never existed.
 */
class TranslationsController extends Controller
{
    public function index()
    {
        return response()->json([
            'locale' => App::getLocale(),
            'dashboard' => [
                'welcome' => __('dashboard.doctor.welcome'),
                'verification_status' => __('dashboard.doctor.verification_status'),
                'specialties' => __('dashboard.doctor.specialties'),
                'none_selected' => __('dashboard.doctor.none_selected'),
                'consultation_fee' => __('dashboard.doctor.consultation_fee'),
                'availability_title' => __('dashboard.doctor.availability_title'),
                'availability_desc' => __('dashboard.doctor.availability_desc'),
                'manage_availability' => __('dashboard.doctor.manage_availability'),
                'appointments_title' => __('dashboard.doctor.appointments_title'),
                'appointments_desc' => __('dashboard.doctor.appointments_desc'),
                'view_appointments' => __('dashboard.doctor.view_appointments'),
                'records_title' => __('dashboard.doctor.records_title'),
                'records_desc' => __('dashboard.doctor.records_desc'),
                'patient_records' => __('dashboard.doctor.patient_records'),
                'inbox_title' => __('dashboard.doctor.inbox_title'),
                'inbox_desc' => __('dashboard.doctor.inbox_desc'),
                'open_inbox' => __('dashboard.doctor.open_inbox'),
                'reviews_title' => __('dashboard.doctor.reviews_title'),
                'reviews_desc' => __('dashboard.doctor.reviews_desc'),
                'my_reviews' => __('dashboard.doctor.my_reviews'),
                'analytics_title' => __('dashboard.doctor.analytics_title'),
                'analytics_desc' => __('dashboard.doctor.analytics_desc'),
                'view_analytics' => __('dashboard.doctor.view_analytics'),
                'here_today' => __('dashboard.doctor.here_today'),
                'stat_today_appointments' => __('dashboard.doctor.stat_today_appointments'),
                'stat_pending_appointments' => __('dashboard.doctor.stat_pending_appointments'),
                'stat_video_consultations' => __('dashboard.doctor.stat_video_consultations'),
                'stat_today_earnings' => __('dashboard.doctor.stat_today_earnings'),
                'today_appointments_title' => __('dashboard.doctor.today_appointments_title'),
                'no_today_appointments' => __('dashboard.doctor.no_today_appointments'),
                'view_all_appointments' => __('dashboard.doctor.view_all_appointments'),
                'calendar_title' => __('dashboard.doctor.calendar_title'),
                'patient_overview_title' => __('dashboard.doctor.patient_overview_title'),
                'donut_total_label' => __('dashboard.doctor.donut_total_label'),
                'overview_new' => __('dashboard.doctor.overview_new'),
                'overview_follow_up' => __('dashboard.doctor.overview_follow_up'),
                'overview_returning' => __('dashboard.doctor.overview_returning'),
                'no_patient_history' => __('dashboard.doctor.no_patient_history'),
                'appt_status_completed' => __('dashboard.doctor.appt_status_completed'),
                'appt_status_in_progress' => __('dashboard.doctor.appt_status_in_progress'),
                'appt_status_upcoming' => __('dashboard.doctor.appt_status_upcoming'),
                'appt_status_no_show' => __('dashboard.doctor.appt_status_no_show'),
                'online' => __('patient.book.online'),
                'onsite' => __('patient.book.onsite'),
            ],
        ]);
    }
}

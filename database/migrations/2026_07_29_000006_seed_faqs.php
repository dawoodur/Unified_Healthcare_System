<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seeds a starter set of platform FAQs the rule-based chatbot matches
 * against (see FaqController) — same keyword-matching approach as
 * symptom_specialty_map, just answering "how do I..." questions about
 * the platform itself instead of suggesting a specialty.
 */
return new class extends Migration
{
    public function up(): void
    {
        // [question, answer, category, [keywords]]
        $faqs = [
            ['How do I book an appointment?', 'Go to "Find a doctor" from your dashboard, pick a doctor, then pick an open date and visiting window. You\'ll get a queue serial number instead of a personal time slot.', 'Appointments', ['book appointment', 'booking', 'schedule appointment']],
            ['How does the OTP code work?', "Every login requires a password plus a 6-digit code emailed to you (check Mailpit if you're testing locally). It expires after a few minutes, and you can request a new one if it does.", 'Account', ['otp', 'verification code', 'two-factor', '2fa']],
            ['How do I cancel an appointment?', 'Go to "My appointments" and click Cancel on any pending appointment. If you already paid, it\'s automatically refunded. If someone was waitlisted for that same window, they\'ll be notified.', 'Appointments', ['cancel appointment', 'cancellation', 'cancel booking']],
            ['What is a serial number?', "Instead of a personal time slot, everyone booked into the same visiting window gets a queue number in the order they booked — first to book is #1. The doctor updates \"now serving #X\" as they go.", 'Appointments', ['serial number', 'queue number', 'serial']],
            ['How do I get a refund?', 'Refunds happen automatically: if a doctor never marks your visit complete (a no-show on their end) or you cancel a paid appointment, the payment is refunded on the spot — no separate request needed.', 'Payments', ['refund', 'money back', 'get my money back']],
            ['How do reward points work?', 'You earn 1 point for every new rating you leave. Every 10 points is worth 1% off a medicine order at checkout, up to 100 points (10%) per order.', 'Rewards', ['reward points', 'points', 'loyalty points', 'discount']],
            ['How do I request a major operation?', 'From a hospital\'s Surgery listing, click "Request" instead of "Book". The hospital will respond with a doctor, date, time, and serial number for you to accept and pay for.', 'Operations', ['operation', 'surgery request', 'major operation']],
            ['How often can I donate blood?', "Every 3 months. Log a donation from your dashboard and you'll see exactly when you're next eligible.", 'Blood Donation', ['blood donation', 'donate blood', 'blood eligible', 'how often donate blood']],
            ['How do I message a hospital or pharmacy?', 'Use the Inbox from your dashboard — you can only message roles you\'re allowed to coordinate with directly (e.g. a patient can message a hospital or pharmacy, but not a doctor directly outside an appointment).', 'Messaging', ['inbox', 'message', 'contact hospital', 'chat']],
            ['How do I track my medicine order?', 'Check "My orders" from your dashboard — it shows the current status (placed, accepted, out for delivery, delivered) and which delivery agent has it once assigned.', 'Orders', ['track order', 'medicine order', 'delivery status', 'where is my order']],
            ['Are my reviews anonymous?', "Yes — when you rate a doctor, hospital, pharmacy, or delivery agent, they only ever see your star rating and comment, never your name or account.", 'Reviews', ['review', 'rating', 'anonymous', 'do they see my name']],
            ['How does a doctor get access to my medical records?', 'A doctor has to request access, and you get an email with an approval OTP — nothing is shared until you approve it, and access expires automatically after the granted window.', 'Medical Records', ['medical records', 'record access', 'otp approval', 'doctor access my records']],
            ['What payment methods are accepted?', 'Right now, Cash is the only fully working option (bKash needs real sandbox credentials to activate) — you pay in cash at the time of visit or delivery.', 'Payments', ['payment', 'payment method', 'cash', 'bkash']],
            ['How do I change the site language?', 'Click "EN" or "বাংলা" in the top-right corner of any page — your choice is remembered for future visits.', 'Settings', ['language', 'bangla', 'bengali', 'change language']],
            ['How do I switch to dark mode?', 'Click the "Dark"/"Light" button in the top-right corner of any page — it\'s remembered on this device for next time.', 'Settings', ['dark mode', 'theme', 'night mode', 'light mode']],
        ];

        foreach ($faqs as [$question, $answer, $category, $keywords]) {
            $faqId = DB::table('faqs')->insertGetId([
                'question' => $question,
                'answer' => $answer,
                'category' => $category,
            ], 'faq_id');

            DB::table('faq_keywords')->insert(
                collect($keywords)->map(fn ($keyword) => ['faq_id' => $faqId, 'keyword' => $keyword])->all()
            );
        }
    }

    public function down(): void
    {
        DB::table('faqs')->truncate();
    }
};

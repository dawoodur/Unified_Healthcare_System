<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A small pool of general health tips shown on the patient dashboard —
 * see DashboardController::patient() for how "today's tip" is picked
 * (deterministic per day, not random per page load, so it doesn't change
 * every time the dashboard is refreshed).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('health_tips', function (Blueprint $table) {
            $table->id('tip_id');
            $table->string('tip_text', 255);
            $table->string('icon', 40)->default('bi-heart-pulse');
        });

        $tips = [
            ['Drink at least 8 glasses of water daily to stay hydrated.', 'bi-cup-straw'],
            ['Aim for 7-9 hours of sleep every night for better recovery.', 'bi-moon-stars'],
            ['Take a 10-minute walk after meals to help digestion.', 'bi-person-walking'],
            ['Wash your hands regularly to avoid infections.', 'bi-droplet-half'],
            ['Eat more fruits and vegetables — aim for 5 servings a day.', 'bi-apple'],
            ['Limit added sugar and salt in your daily diet.', 'bi-egg-fried'],
            ['Take short breaks from screens every 30 minutes to rest your eyes.', 'bi-eye'],
            ['Stretch for a few minutes each morning to loosen up your muscles.', 'bi-arrows-angle-expand'],
            ['Don\'t skip breakfast — it kickstarts your metabolism for the day.', 'bi-sunrise'],
            ['Get at least 30 minutes of physical activity most days of the week.', 'bi-heart-pulse'],
            ['Practice deep breathing for a few minutes to reduce stress.', 'bi-wind'],
            ['Check your blood pressure regularly, especially if it runs in the family.', 'bi-activity'],
            ['Don\'t ignore persistent symptoms — book a doctor early rather than waiting.', 'bi-calendar2-check'],
            ['Keep your vaccinations up to date.', 'bi-shield-plus'],
            ['Maintain good posture while sitting for long periods.', 'bi-person-standing'],
            ['Limit caffeine in the evening for better sleep quality.', 'bi-cup-hot'],
            ['Floss daily — it prevents more than just cavities.', 'bi-emoji-smile'],
            ['Take your prescribed medicines on schedule for them to work properly.', 'bi-capsule'],
            ['Spend a few minutes outdoors in sunlight for natural vitamin D.', 'bi-brightness-high'],
            ['A brief daily journal can help you track your mood and health patterns.', 'bi-journal-text'],
        ];

        foreach ($tips as [$text, $icon]) {
            DB::table('health_tips')->insert(['tip_text' => $text, 'icon' => $icon]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('health_tips');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the keyword -> specialty lookup the rule-based symptom checker
 * matches against (see SymptomCheckerController). `weight` is just "how
 * strong a signal is this keyword" — a very specific term like
 * "migraine" scores higher than a generic one like "pain" that could
 * belong to several specialties.
 */
return new class extends Migration
{
    public function up(): void
    {
        $bySpecialtyName = DB::table('specialties')->pluck('specialty_id', 'specialty_name');

        // [specialty name => [keyword => weight, ...], ...]
        $map = [
            'General Physician' => ['fever' => 2, 'cold' => 2, 'flu' => 2, 'fatigue' => 1, 'body ache' => 1, 'weakness' => 1],
            'Cardiology' => ['chest pain' => 3, 'heart palpitations' => 3, 'irregular heartbeat' => 3, 'high blood pressure' => 2, 'shortness of breath' => 2],
            'Dermatology' => ['skin rash' => 3, 'eczema' => 3, 'acne' => 2, 'itching' => 2, 'hair loss' => 2, 'skin allergy' => 2],
            'Pediatrics' => ['child fever' => 3, 'baby cough' => 2, 'infant' => 2, 'growth delay' => 2, 'child rash' => 2],
            'Gynecology' => ['menstrual pain' => 3, 'irregular periods' => 3, 'pregnancy' => 2, 'pelvic pain' => 2],
            'Orthopedics' => ['joint pain' => 3, 'back pain' => 3, 'fracture' => 3, 'knee pain' => 2, 'bone pain' => 2, 'muscle pain' => 1],
            'Neurology' => ['migraine' => 3, 'seizure' => 3, 'headache' => 2, 'numbness' => 2, 'memory loss' => 2, 'dizziness' => 1],
            'Psychiatry' => ['anxiety' => 3, 'depression' => 3, 'panic attack' => 3, 'insomnia' => 2, 'stress' => 1],
            'ENT' => ['ear pain' => 3, 'hearing loss' => 3, 'sore throat' => 2, 'sinus' => 2, 'nose bleed' => 2],
            'Ophthalmology' => ['blurred vision' => 3, 'eye pain' => 3, 'red eyes' => 2, 'eye itching' => 2],
            'Gastroenterology' => ['acid reflux' => 3, 'stomach pain' => 2, 'diarrhea' => 2, 'constipation' => 2, 'nausea' => 1, 'vomiting' => 1],
            'Endocrinology' => ['diabetes' => 3, 'thyroid' => 3, 'excessive thirst' => 2, 'weight gain' => 1, 'weight loss' => 1],
            'Urology' => ['urinary infection' => 3, 'kidney stone' => 3, 'painful urination' => 3, 'frequent urination' => 2],
            'Pulmonology' => ['breathing difficulty' => 3, 'asthma' => 3, 'wheezing' => 2, 'chest congestion' => 2, 'cough' => 1],
            'Dentistry' => ['tooth pain' => 3, 'toothache' => 3, 'gum bleeding' => 2, 'tooth sensitivity' => 2, 'bad breath' => 1],
        ];

        $rows = [];
        foreach ($map as $specialtyName => $keywords) {
            $specialtyId = $bySpecialtyName[$specialtyName] ?? null;
            if (!$specialtyId) {
                continue; // specialty not seeded on this install — skip rather than fail
            }

            foreach ($keywords as $keyword => $weight) {
                $rows[] = ['keyword' => $keyword, 'specialty_id' => $specialtyId, 'weight' => $weight];
            }
        }

        if ($rows) {
            DB::table('symptom_specialty_map')->insertOrIgnore($rows);
        }
    }

    public function down(): void
    {
        DB::table('symptom_specialty_map')->truncate();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seeds a starter set of common conditions for the symptom checker's
 * "possible conditions" section — same keyword-matching approach as
 * symptom_specialty_map/faq_keywords. This is a rule-based educational
 * tool, not a diagnostic one: the advice text for anything serious always
 * says to see a doctor (or seek emergency care), never "you have X."
 */
return new class extends Migration
{
    public function up(): void
    {
        // [name, advice, [keywords]]
        $diseases = [
            ['Common Cold', 'Usually clears up on its own in 7-10 days with rest and fluids. See a doctor if symptoms last longer than 10 days or get worse.', ['runny nose', 'stuffy nose', 'sneezing', 'mild cough', 'sore throat']],
            ['Seasonal Flu (Influenza)', 'Rest, fluids, and fever-reducing medicine usually help. See a doctor if fever is very high, lasts more than 3 days, or breathing becomes difficult.', ['fever', 'body ache', 'chills', 'fatigue', 'flu']],
            ['Migraine', 'Resting in a dark, quiet room can help. See a doctor if headaches are frequent, severe, or new for you.', ['throbbing headache', 'light sensitivity', 'migraine', 'nausea with headache']],
            ['Tension Headache', 'Often linked to stress or posture. See a doctor if it happens often or doesn\'t respond to rest and mild pain relief.', ['dull headache', 'head pressure', 'stress headache']],
            ['Gastroenteritis (Stomach Flu)', 'Stay hydrated with small, frequent sips of fluid. See a doctor if you can\'t keep fluids down, or symptoms last more than 2 days.', ['diarrhea', 'vomiting', 'stomach cramps', 'nausea']],
            ['Food Poisoning', 'Usually improves within 24-48 hours with rest and hydration. See a doctor if there\'s blood in vomit/stool, high fever, or signs of dehydration.', ['food poisoning', 'vomiting after eating', 'stomach pain after eating']],
            ['Urinary Tract Infection', 'A UTI usually needs a doctor\'s evaluation and often antibiotics — don\'t wait it out.', ['burning urination', 'frequent urination', 'urine pain', 'uti']],
            ['Acid Reflux (GERD)', 'Avoiding large/late meals can help. See a doctor if it happens often, as it may need treatment.', ['heartburn', 'acid reflux', 'burning chest after eating', 'sour taste']],
            ['Seasonal Allergy', 'Avoiding the trigger and antihistamines often help. See a doctor if symptoms are severe or year-round.', ['itchy eyes', 'watery eyes', 'allergy', 'hay fever']],
            ['Conjunctivitis (Pink Eye)', 'Can be contagious — avoid touching/rubbing your eyes and see a doctor for proper treatment.', ['red eye', 'itchy eye', 'eye discharge', 'pink eye']],
            ['Muscle Strain', 'Rest, ice, and gentle stretching often help. See a doctor if pain is severe or doesn\'t improve in a few days.', ['muscle pain', 'sore muscles', 'pulled muscle', 'back strain']],
            ['Stress-Related Sleep Trouble', 'Good sleep habits can help. See a doctor if it continues for weeks or affects daily life.', ['can\'t sleep', 'trouble sleeping', 'insomnia', 'sleepless']],
            ['Possible Early Diabetes Signs', 'These signs are worth getting checked with a blood sugar test — please see a doctor rather than self-treating.', ['excessive thirst', 'frequent urination', 'unexplained weight loss', 'always hungry']],
            ['Possible High Blood Pressure', 'These signs are worth getting your blood pressure checked by a doctor.', ['frequent headache', 'dizziness', 'blurred vision', 'high blood pressure']],
            ['Possible Cardiac Concern', 'This combination of symptoms can indicate a serious heart-related issue. Seek emergency medical care immediately — do not wait or self-treat.', ['chest pain', 'shortness of breath', 'chest tightness', 'pain radiating to arm']],
        ];

        foreach ($diseases as [$name, $advice, $keywords]) {
            $diseaseId = DB::table('diseases')->insertGetId([
                'name' => $name,
                'advice' => $advice,
            ], 'disease_id');

            DB::table('disease_keywords')->insert(
                collect($keywords)->map(fn ($keyword) => ['disease_id' => $diseaseId, 'keyword' => $keyword])->all()
            );
        }
    }

    public function down(): void
    {
        DB::table('diseases')->truncate();
    }
};

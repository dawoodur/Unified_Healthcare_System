<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Bangla spellings for the medicines in the catalog, so a patient typing
 * "নাপা কী কাজে লাগে" is understood — brand and generic names are stored in
 * Latin, which a Bangla-script message never matches (see
 * FirstAidChatService::matchMedicine).
 *
 * Keyed by brand name, and the generic's Bangla spelling is added to every
 * brand of that generic so "প্যারাসিটামল" works too.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicine_master', function (Blueprint $table) {
            $table->json('aliases')->nullable();
        });

        $genericBn = [
            'Paracetamol' => 'প্যারাসিটামল',
            'Ibuprofen' => 'আইবুপ্রোফেন',
            'Omeprazole' => 'ওমিপ্রাজল',
            'Esomeprazole' => 'ইসোমিপ্রাজল',
            'Famotidine' => 'ফ্যামোটিডিন',
            'Cetirizine' => 'সিটিরিজিন',
            'Fexofenadine' => 'ফেক্সোফেনাডিন',
            'Ambroxol' => 'অ্যামব্রক্সল',
            'Oral Rehydration Salt' => 'খাবার স্যালাইন',
            'Loperamide' => 'লোপেরামাইড',
            'Multivitamin' => 'মাল্টিভিটামিন',
            'Amoxicillin' => 'অ্যামোক্সিসিলিন',
            'Azithromycin' => 'অ্যাজিথ্রোমাইসিন',
            'Ciprofloxacin' => 'সিপ্রোফ্লক্সাসিন',
            'Metformin' => 'মেটফরমিন',
            'Glimepiride' => 'গ্লিমেপিরাইড',
            'Amlodipine' => 'অ্যামলোডিপিন',
            'Losartan' => 'লোসারটান',
            'Atorvastatin' => 'অ্যাটোরভাস্ট্যাটিন',
        ];

        $brandBn = [
            'Napa' => ['নাপা'],
            'Brufen' => ['ব্রুফেন'],
            'Adol' => ['অ্যাডল', 'এডল'],
            'Seclo' => ['সেক্লো'],
            'Nexum' => ['নেক্সাম'],
            'Famotid' => ['ফ্যামোটিড'],
            'Alatrol' => ['অ্যালাট্রল', 'এলাট্রল'],
            'Fexo' => ['ফেক্সো'],
            'Ambrox' => ['অ্যামব্রক্স'],
            'ORSaline' => ['ওরস্যালাইন', 'ওআরএস', 'খাবার স্যালাইন'],
            'ORS-N' => ['ওআরএস', 'খাবার স্যালাইন'],
            'Imodex' => ['ইমোডেক্স'],
            'Centro' => ['সেন্ট্রো'],
            'Amodis' => ['অ্যামোডিস'],
            'Azithrocin' => ['অ্যাজিথ্রোসিন'],
            'Zimax' => ['জিম্যাক্স'],
            'Ciprocin' => ['সিপ্রোসিন'],
            'Comid' => ['কমিড'],
            'Amaryl' => ['অ্যামারিল'],
            'Amdocal' => ['অ্যামডোকল'],
            'Losartrix' => ['লোসারট্রিক্স'],
            'Lopres' => ['লোপ্রেস'],
            'Atorva' => ['অ্যাটোরভা'],
        ];

        foreach (DB::table('medicine_master')->get() as $row) {
            $aliases = $brandBn[$row->brand_name] ?? [];

            if (isset($genericBn[$row->generic_name])) {
                $aliases[] = $genericBn[$row->generic_name];
            }

            if ($aliases === []) {
                continue;
            }

            DB::table('medicine_master')
                ->where('medicine_master_id', $row->medicine_master_id)
                ->update(['aliases' => json_encode(array_values(array_unique($aliases)), JSON_UNESCAPED_UNICODE)]);
        }
    }

    public function down(): void
    {
        Schema::table('medicine_master', function (Blueprint $table) {
            $table->dropColumn('aliases');
        });
    }
};

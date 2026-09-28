<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Plain-language information for the medicines already in the catalog, so a
 * patient can ask the health chat "what is Napa for?" (see
 * FirstAidChatService::medicineReply).
 *
 * Deliberately NOT dosing advice: every entry says what the medicine is
 * generally used for and what to be careful about, and the chat always adds
 * "follow the packet or your doctor". Antibiotics and long-term medicines
 * are flagged prescription-only so the chat tells patients not to
 * self-medicate with them — self-prescribed antibiotics are a real problem
 * here, and this app must not encourage it.
 *
 * Written against the generic name, so every brand of the same generic
 * (Napa and any other paracetamol) is covered by one entry.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medicine_master', function (Blueprint $table) {
            $table->text('uses_en')->nullable();
            $table->text('uses_bn')->nullable();
            $table->json('cautions_en')->nullable();
            $table->json('cautions_bn')->nullable();
            $table->boolean('is_prescription_only')->default(false);
        });

        foreach ($this->info() as $generic => $info) {
            DB::table('medicine_master')
                ->whereRaw('LOWER(generic_name) = ?', [mb_strtolower($generic)])
                ->update([
                    'uses_en' => $info['uses_en'],
                    'uses_bn' => $info['uses_bn'],
                    'cautions_en' => json_encode($info['cautions_en'], JSON_UNESCAPED_UNICODE),
                    'cautions_bn' => json_encode($info['cautions_bn'], JSON_UNESCAPED_UNICODE),
                    'is_prescription_only' => $info['rx'],
                ]);
        }
    }

    public function down(): void
    {
        Schema::table('medicine_master', function (Blueprint $table) {
            $table->dropColumn(['uses_en', 'uses_bn', 'cautions_en', 'cautions_bn', 'is_prescription_only']);
        });
    }

    private function info(): array
    {
        return [
            'Paracetamol' => [
                'rx' => false,
                'uses_en' => 'Brings down fever and eases mild to moderate pain such as headache, body ache, toothache and period pain.',
                'uses_bn' => 'জ্বর কমায় এবং মাথাব্যথা, গা ব্যথা, দাঁত ব্যথা ও মাসিকের ব্যথার মতো হালকা থেকে মাঝারি ব্যথা কমায়।',
                'cautions_en' => ['Never take more than the packet allows in 24 hours — too much damages the liver.', 'Check other medicines you are taking; many cold and flu products already contain paracetamol.', 'Tell a doctor if you have liver disease or drink alcohol regularly.'],
                'cautions_bn' => ['২৪ ঘণ্টায় প্যাকেটে লেখা মাত্রার বেশি খাবেন না — বেশি হলে লিভার ক্ষতিগ্রস্ত হয়।', 'অন্য ওষুধ দেখে নিন; অনেক সর্দি-জ্বরের ওষুধেই প্যারাসিটামল থাকে।', 'লিভারের রোগ থাকলে বা নিয়মিত অ্যালকোহল নিলে চিকিৎসককে জানান।'],
            ],
            'Ibuprofen' => [
                'rx' => false,
                'uses_en' => 'Reduces pain, swelling and fever — often used for period pain, muscle or joint pain and injuries.',
                'uses_bn' => 'ব্যথা, ফোলা ও জ্বর কমায় — সাধারণত মাসিকের ব্যথা, মাংসপেশি বা গিঁটের ব্যথা ও আঘাতে ব্যবহার হয়।',
                'cautions_en' => ['Take it after food — on an empty stomach it can cause gastric pain or ulcers.', 'Avoid if you have a stomach ulcer, kidney disease, or are in late pregnancy.', 'Avoid during dengue or unexplained fever — it can increase bleeding risk.'],
                'cautions_bn' => ['খাবারের পর খাবেন — খালি পেটে গ্যাস্ট্রিক বা আলসার হতে পারে।', 'পেটে আলসার, কিডনির রোগ থাকলে বা গর্ভাবস্থার শেষ দিকে এড়িয়ে চলুন।', 'ডেঙ্গু বা অজানা জ্বরে এড়িয়ে চলুন — রক্তক্ষরণের ঝুঁকি বাড়াতে পারে।'],
            ],
            'Omeprazole' => [
                'rx' => false,
                'uses_en' => 'Reduces stomach acid — used for acidity, heartburn, gastric pain and stomach ulcers.',
                'uses_bn' => 'পাকস্থলীর অ্যাসিড কমায় — গ্যাস্ট্রিক, বুক জ্বালা, পেট ব্যথা ও আলসারে ব্যবহার হয়।',
                'cautions_en' => ['Usually taken before food, as directed on the packet.', 'See a doctor rather than taking it for months on end.', 'Tell a doctor about black stools, vomiting blood, or weight loss instead of self-treating.'],
                'cautions_bn' => ['সাধারণত খাবারের আগে, প্যাকেটের নির্দেশমতো খেতে হয়।', 'মাসের পর মাস নিজে খেতে থাকবেন না, চিকিৎসক দেখান।', 'কালো পায়খানা, রক্তবমি বা ওজন কমলে নিজে চিকিৎসা না করে চিকিৎসকের কাছে যান।'],
            ],
            'Esomeprazole' => [
                'rx' => false,
                'uses_en' => 'Reduces stomach acid, similar to omeprazole — used for acid reflux, heartburn and ulcers.',
                'uses_bn' => 'ওমিপ্রাজলের মতোই পাকস্থলীর অ্যাসিড কমায় — অ্যাসিড রিফ্লাক্স, বুক জ্বালা ও আলসারে ব্যবহার হয়।',
                'cautions_en' => ['Take as directed on the packet, usually before a meal.', 'Long-term use should be supervised by a doctor.', 'Chest pain spreading to the arm or jaw is not acidity — call 999.'],
                'cautions_bn' => ['প্যাকেটের নির্দেশমতো, সাধারণত খাবারের আগে খাবেন।', 'দীর্ঘদিন ব্যবহার চিকিৎসকের তত্ত্বাবধানে হওয়া উচিত।', 'বুকে ব্যথা হাত বা চোয়ালে ছড়ালে সেটি গ্যাস্ট্রিক নয় — ৯৯৯-এ কল করুন।'],
            ],
            'Famotidine' => [
                'rx' => false,
                'uses_en' => 'Lowers stomach acid — used for heartburn, acidity and ulcer treatment.',
                'uses_bn' => 'পাকস্থলীর অ্যাসিড কমায় — বুক জ্বালা, গ্যাস্ট্রিক ও আলসারের চিকিৎসায় ব্যবহার হয়।',
                'cautions_en' => ['Follow the dose on the packet; do not double up with other acid medicines without advice.', 'Tell a doctor if you have kidney problems.'],
                'cautions_bn' => ['প্যাকেটের মাত্রা মেনে চলুন; পরামর্শ ছাড়া অন্য অ্যাসিডের ওষুধের সাথে একসাথে খাবেন না।', 'কিডনির সমস্যা থাকলে চিকিৎসককে জানান।'],
            ],
            'Cetirizine' => [
                'rx' => false,
                'uses_en' => 'An antihistamine for allergy symptoms — itching, rash, sneezing, runny nose and watery eyes.',
                'uses_bn' => 'অ্যালার্জির ওষুধ — চুলকানি, র‍্যাশ, হাঁচি, নাক দিয়ে পানি পড়া ও চোখে পানি আসায় ব্যবহার হয়।',
                'cautions_en' => ['It can make you sleepy — avoid driving or machinery until you know how it affects you.', 'Swelling of the lips or face with breathing trouble needs emergency care, not just a tablet.'],
                'cautions_bn' => ['ঘুম ঘুম লাগতে পারে — কেমন প্রভাব ফেলে না বোঝা পর্যন্ত গাড়ি বা মেশিন চালাবেন না।', 'ঠোঁট বা মুখ ফুলে শ্বাসকষ্ট হলে শুধু ট্যাবলেট নয়, জরুরি চিকিৎসা নিন।'],
            ],
            'Fexofenadine' => [
                'rx' => false,
                'uses_en' => 'An antihistamine for allergy symptoms such as sneezing, runny nose, itching and hives — usually causes less drowsiness.',
                'uses_bn' => 'অ্যালার্জির ওষুধ — হাঁচি, নাক দিয়ে পানি, চুলকানি ও আমবাতে ব্যবহার হয়; সাধারণত ঘুম কম আনে।',
                'cautions_en' => ['Avoid taking it with fruit juice, which reduces how well it works.', 'See a doctor if allergy symptoms keep coming back.'],
                'cautions_bn' => ['ফলের রসের সাথে খাবেন না, এতে কাজ কম হয়।', 'অ্যালার্জি বারবার ফিরে এলে চিকিৎসক দেখান।'],
            ],
            'Ambroxol' => [
                'rx' => false,
                'uses_en' => 'Loosens thick mucus so a chesty cough clears more easily.',
                'uses_bn' => 'ঘন কফ পাতলা করে, ফলে কাশির সাথে কফ সহজে বের হয়।',
                'cautions_en' => ['Drink plenty of water while taking it.', 'A cough lasting more than three weeks, or with blood, needs a doctor.'],
                'cautions_bn' => ['খাওয়ার সময় প্রচুর পানি পান করুন।', 'তিন সপ্তাহের বেশি কাশি, বা কাশির সাথে রক্ত গেলে চিকিৎসক দেখান।'],
            ],
            'Oral Rehydration Salt' => [
                'rx' => false,
                'uses_en' => 'Replaces the water and salts lost in diarrhoea, vomiting or heavy sweating — the first treatment for dehydration.',
                'uses_bn' => 'ডায়রিয়া, বমি বা অতিরিক্ত ঘামে হারানো পানি ও লবণ পূরণ করে — পানিশূন্যতার প্রথম চিকিৎসা।',
                'cautions_en' => ['Mix one packet in the amount of clean water written on the packet — usually half a litre. Never mix it stronger.', 'Use within 12 hours of mixing.', 'Blood in the stool, or a child who stays weak, needs a doctor.'],
                'cautions_bn' => ['প্যাকেটে লেখা পরিমাণ পরিষ্কার পানিতে এক প্যাকেট মেশান — সাধারণত আধা লিটার। কখনও ঘন করে বানাবেন না।', 'মেশানোর ১২ ঘণ্টার মধ্যে ব্যবহার করুন।', 'পায়খানায় রক্ত গেলে, বা শিশু দুর্বল থাকলে চিকিৎসক দেখান।'],
            ],
            'Loperamide' => [
                'rx' => false,
                'uses_en' => 'Slows the bowel to reduce the number of loose motions in simple diarrhoea.',
                'uses_bn' => 'সাধারণ ডায়রিয়ায় পাতলা পায়খানার সংখ্যা কমায়।',
                'cautions_en' => ['It does not treat the cause — ORS and fluids remain the main treatment.', 'Do NOT use if there is blood in the stool or a high fever.', 'Not for young children unless a doctor says so.'],
                'cautions_bn' => ['এটি কারণ সারায় না — ওরস্যালাইন ও তরলই মূল চিকিৎসা।', 'পায়খানায় রক্ত থাকলে বা বেশি জ্বর থাকলে খাবেন না।', 'চিকিৎসক না বললে ছোট শিশুদের দেবেন না।'],
            ],
            'Multivitamin' => [
                'rx' => false,
                'uses_en' => 'A vitamin and mineral supplement used when the diet is lacking or during recovery from illness.',
                'uses_bn' => 'খাবারে ঘাটতি থাকলে বা অসুস্থতার পর সেরে ওঠার সময় ব্যবহৃত ভিটামিন ও মিনারেল সাপ্লিমেন্ট।',
                'cautions_en' => ['A supplement does not replace proper meals.', 'Do not take several vitamin products together — too much of some vitamins is harmful.'],
                'cautions_bn' => ['সাপ্লিমেন্ট সুষম খাবারের বিকল্প নয়।', 'একসাথে কয়েকটি ভিটামিন পণ্য খাবেন না — কিছু ভিটামিন বেশি হলে ক্ষতিকর।'],
            ],
            'Amoxicillin' => [
                'rx' => true,
                'uses_en' => 'An antibiotic for bacterial infections such as some chest, ear, throat and urine infections.',
                'uses_bn' => 'অ্যান্টিবায়োটিক — বুকে, কানে, গলায় ও প্রস্রাবের কিছু ব্যাকটেরিয়াজনিত সংক্রমণে ব্যবহার হয়।',
                'cautions_en' => ['Antibiotics do not work on colds and flu, which are viral.', 'Finish the whole course even after you feel better, or the infection can come back stronger.', 'Tell the doctor if you have ever had a penicillin allergy.'],
                'cautions_bn' => ['সর্দি-ফ্লুতে অ্যান্টিবায়োটিক কাজ করে না, কারণ সেগুলো ভাইরাসজনিত।', 'ভালো লাগলেও পুরো কোর্স শেষ করুন, নইলে সংক্রমণ আরও শক্তিশালী হয়ে ফিরতে পারে।', 'পেনিসিলিনে অ্যালার্জি থাকলে চিকিৎসককে জানান।'],
            ],
            'Azithromycin' => [
                'rx' => true,
                'uses_en' => 'An antibiotic used for certain chest, throat, skin and other bacterial infections.',
                'uses_bn' => 'অ্যান্টিবায়োটিক — বুকে, গলায়, ত্বকে ও অন্যান্য কিছু ব্যাকটেরিয়াজনিত সংক্রমণে ব্যবহার হয়।',
                'cautions_en' => ['Only take it if a doctor prescribed it for you — not left over from someone else.', 'Complete the full course exactly as prescribed.', 'Tell your doctor about heart rhythm problems before taking it.'],
                'cautions_bn' => ['চিকিৎসক আপনাকে লিখে দিলে তবেই খাবেন — অন্যের ওষুধ খাবেন না।', 'যেভাবে লেখা আছে ঠিক সেভাবেই পুরো কোর্স শেষ করুন।', 'হৃৎস্পন্দনের সমস্যা থাকলে খাওয়ার আগে চিকিৎসককে জানান।'],
            ],
            'Ciprofloxacin' => [
                'rx' => true,
                'uses_en' => 'An antibiotic used for some urine, gut and other bacterial infections.',
                'uses_bn' => 'অ্যান্টিবায়োটিক — প্রস্রাব, পেট ও কিছু ব্যাকটেরিয়াজনিত সংক্রমণে ব্যবহার হয়।',
                'cautions_en' => ['Prescription only — never take it for an ordinary cold or loose motion.', 'Stop and contact a doctor if you get tendon pain or unusual numbness.', 'Avoid taking it at the same time as milk, antacids or iron tablets.'],
                'cautions_bn' => ['শুধু প্রেসক্রিপশনে — সাধারণ সর্দি বা পাতলা পায়খানায় কখনো খাবেন না।', 'রগে ব্যথা বা অস্বাভাবিক অবশ ভাব হলে বন্ধ করে চিকিৎসকের সাথে যোগাযোগ করুন।', 'দুধ, অ্যান্টাসিড বা আয়রন ট্যাবলেটের সাথে একই সময়ে খাবেন না।'],
            ],
            'Metformin' => [
                'rx' => true,
                'uses_en' => 'A long-term medicine that lowers blood sugar in type 2 diabetes.',
                'uses_bn' => 'টাইপ-২ ডায়াবেটিসে রক্তে চিনি কমানোর দীর্ঘমেয়াদি ওষুধ।',
                'cautions_en' => ['Take it with food to reduce stomach upset.', 'Do not start, stop or change the dose yourself.', 'Tell your doctor if you have kidney problems or are having a scan with contrast dye.'],
                'cautions_bn' => ['পেটের অস্বস্তি কমাতে খাবারের সাথে খাবেন।', 'নিজে থেকে শুরু, বন্ধ বা মাত্রা পরিবর্তন করবেন না।', 'কিডনির সমস্যা থাকলে বা কনট্রাস্ট ডাই দিয়ে স্ক্যান করালে চিকিৎসককে জানান।'],
            ],
            'Glimepiride' => [
                'rx' => true,
                'uses_en' => 'A long-term diabetes medicine that helps the body release more insulin.',
                'uses_bn' => 'ডায়াবেটিসের দীর্ঘমেয়াদি ওষুধ — শরীরকে বেশি ইনসুলিন ছাড়তে সাহায্য করে।',
                'cautions_en' => ['Can drop blood sugar too low — do not skip meals after taking it.', 'Learn the signs of low sugar: shaking, sweating, confusion. Take sugar immediately if they appear.', 'Dose changes are for your doctor to make.'],
                'cautions_bn' => ['রক্তে চিনি খুব কমে যেতে পারে — খেয়ে নিয়ে তারপর ওষুধ খাবেন, খাবার বাদ দেবেন না।', 'চিনি কমার লক্ষণ জানুন: কাঁপুনি, ঘাম, বিভ্রান্তি। দেখা দিলে সঙ্গে সঙ্গে চিনি খান।', 'মাত্রা পরিবর্তন চিকিৎসকই করবেন।'],
            ],
            'Amlodipine' => [
                'rx' => true,
                'uses_en' => 'A long-term medicine that lowers high blood pressure and eases chest pain from angina.',
                'uses_bn' => 'উচ্চ রক্তচাপ কমানোর ও অ্যানজাইনার বুকে ব্যথা কমানোর দীর্ঘমেয়াদি ওষুধ।',
                'cautions_en' => ['Keep taking it even when you feel fine — high blood pressure usually has no symptoms.', 'Ankle swelling or dizziness should be reported to your doctor.', 'Never stop it suddenly on your own.'],
                'cautions_bn' => ['ভালো লাগলেও খাওয়া চালিয়ে যান — উচ্চ রক্তচাপে সাধারণত লক্ষণ থাকে না।', 'পায়ের গোড়ালি ফোলা বা মাথা ঘোরা হলে চিকিৎসককে জানান।', 'নিজে থেকে হঠাৎ বন্ধ করবেন না।'],
            ],
            'Losartan' => [
                'rx' => true,
                'uses_en' => 'A long-term medicine for high blood pressure, also used to protect the kidneys in diabetes.',
                'uses_bn' => 'উচ্চ রক্তচাপের দীর্ঘমেয়াদি ওষুধ; ডায়াবেটিসে কিডনি রক্ষায়ও ব্যবহার হয়।',
                'cautions_en' => ['Not safe in pregnancy — tell your doctor if you are pregnant or planning to be.', 'Keep taking it even without symptoms, and do not stop on your own.', 'Your doctor may check kidney function and potassium from time to time.'],
                'cautions_bn' => ['গর্ভাবস্থায় নিরাপদ নয় — গর্ভবতী হলে বা পরিকল্পনা থাকলে চিকিৎসককে জানান।', 'লক্ষণ না থাকলেও খাওয়া চালিয়ে যান, নিজে থেকে বন্ধ করবেন না।', 'চিকিৎসক মাঝে মাঝে কিডনি ও পটাশিয়াম পরীক্ষা করতে পারেন।'],
            ],
            'Atorvastatin' => [
                'rx' => true,
                'uses_en' => 'A long-term medicine that lowers cholesterol to reduce the risk of heart attack and stroke.',
                'uses_bn' => 'কোলেস্টেরল কমিয়ে হার্ট অ্যাটাক ও স্ট্রোকের ঝুঁকি কমানোর দীর্ঘমেয়াদি ওষুধ।',
                'cautions_en' => ['Report unexplained muscle pain or weakness to your doctor.', 'Not for use in pregnancy.', 'It works alongside diet and exercise, not instead of them.'],
                'cautions_bn' => ['অকারণে মাংসপেশিতে ব্যথা বা দুর্বলতা হলে চিকিৎসককে জানান।', 'গর্ভাবস্থায় ব্যবহারের জন্য নয়।', 'এটি খাদ্যাভ্যাস ও ব্যায়ামের পাশাপাশি কাজ করে, বিকল্প নয়।'],
            ],
        ];
    }
};

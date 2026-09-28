<?php

namespace App\Services;

/**
 * Works out what a medicine is broadly for from its generic name, so a
 * medicine a pharmacy adds tomorrow can still be explained without a human
 * writing an entry first.
 *
 * This is not magic and not a drug database: it reads the standard stems
 * that generic drug names are built from (an international naming
 * convention) — "-floxacin" is an antibiotic, "-prazole" reduces stomach
 * acid, "-sartan" and "-dipine" lower blood pressure. That is enough for a
 * safe, class-level answer.
 *
 * Deliberate limits:
 *   - It describes the CLASS, never this specific product, and the chat
 *     labels it as such.
 *   - Anything it cannot place returns null, so the chat says it does not
 *     know instead of guessing.
 *   - Entries it produces are flagged needs_review for an admin to confirm.
 *   - It never produces dosing.
 */
class DrugClassGuesser
{
    /**
     * Longest stems first — "-thromycin" must win over "-mycin", and
     * "-conazole" over "-azole".
     *
     * rx = treat as prescription-only (antibiotics and long-term medicines,
     * where self-medication does real harm).
     */
    private const STEMS = [
        'thromycin' => 'antibiotic',
        'floxacin' => 'antibiotic',
        'cycline' => 'antibiotic',
        'cillin' => 'antibiotic',
        'conazole' => 'antifungal',
        'nidazole' => 'antibiotic',
        'mycin' => 'antibiotic',
        'cefur' => 'antibiotic',
        'cefix' => 'antibiotic',
        'ceftri' => 'antibiotic',
        'prazole' => 'acid_reducer',
        'tidine' => 'acid_reducer',
        'sartan' => 'blood_pressure',
        'dipine' => 'blood_pressure',
        'pril' => 'blood_pressure',
        'olol' => 'heart_rate',
        'statin' => 'cholesterol',
        'formin' => 'diabetes',
        'gliclazide' => 'diabetes',
        'glimepiride' => 'diabetes',
        'gliben' => 'diabetes',
        'gliptin' => 'diabetes',
        'glifloz' => 'diabetes',
        'profen' => 'painkiller_nsaid',
        'oxicam' => 'painkiller_nsaid',
        'fenac' => 'painkiller_nsaid',
        'cetamol' => 'painkiller_basic',
        'tramadol' => 'painkiller_strong',
        'cetirizine' => 'antihistamine',
        'fenadine' => 'antihistamine',
        'ratadine' => 'antihistamine',
        'pheniramine' => 'antihistamine',
        'terol' => 'asthma_inhaler',
        'lukast' => 'asthma_preventer',
        'peridone' => 'anti_nausea',
        'setron' => 'anti_nausea',
        'prostol' => 'ulcer_protect',
        'zepam' => 'sedative',
        'zolam' => 'sedative',
        'vir' => 'antiviral',
        'vitamin' => 'supplement',
        'rehydration' => 'rehydration',
    ];

    private const CLASSES = [
        'antibiotic' => [
            'rx' => true,
            'en' => 'This is an antibiotic. Antibiotics treat bacterial infections — they do not work on colds, flu or most sore throats, which are viral.',
            'bn' => 'এটি একটি অ্যান্টিবায়োটিক। অ্যান্টিবায়োটিক ব্যাকটেরিয়াজনিত সংক্রমণে কাজ করে — সর্দি, ফ্লু বা বেশিরভাগ গলাব্যথায় কাজ করে না, কারণ সেগুলো ভাইরাসজনিত।',
            'cautions_en' => ['Take it only if a doctor prescribed it for you.', 'Finish the full course even after you feel better.', 'Tell your doctor about any drug allergy before starting.'],
            'cautions_bn' => ['চিকিৎসক আপনাকে লিখে দিলে তবেই খাবেন।', 'ভালো লাগলেও পুরো কোর্স শেষ করুন।', 'শুরুর আগে ওষুধে অ্যালার্জি থাকলে চিকিৎসককে জানান।'],
        ],
        'antifungal' => [
            'rx' => true,
            'en' => 'This is an antifungal medicine, used for fungal infections of the skin, nails or elsewhere.',
            'bn' => 'এটি ছত্রাকনাশক ওষুধ — ত্বক, নখ বা অন্যত্র ছত্রাক সংক্রমণে ব্যবহার হয়।',
            'cautions_en' => ['Use it for as long as the doctor says — fungal infections return if stopped early.', 'Tell your doctor about liver problems or other medicines you take.'],
            'cautions_bn' => ['চিকিৎসক যতদিন বলেন ততদিন ব্যবহার করুন — আগে বন্ধ করলে ছত্রাক ফিরে আসে।', 'লিভারের সমস্যা বা অন্য ওষুধ খেলে চিকিৎসককে জানান।'],
        ],
        'acid_reducer' => [
            'rx' => false,
            'en' => 'This medicine reduces stomach acid. Medicines in this group are used for acidity, heartburn, gastric pain and ulcers.',
            'bn' => 'এই ওষুধ পাকস্থলীর অ্যাসিড কমায়। এই শ্রেণির ওষুধ গ্যাস্ট্রিক, বুক জ্বালা, পেট ব্যথা ও আলসারে ব্যবহার হয়।',
            'cautions_en' => ['Usually taken before food — follow the packet.', 'See a doctor instead of taking it for months on end.', 'Black stools or vomiting blood need a doctor, not an antacid.'],
            'cautions_bn' => ['সাধারণত খাবারের আগে খেতে হয় — প্যাকেট দেখে নিন।', 'মাসের পর মাস নিজে খেতে থাকবেন না, চিকিৎসক দেখান।', 'কালো পায়খানা বা রক্তবমি হলে অ্যান্টাসিড নয়, চিকিৎসক দরকার।'],
        ],
        'blood_pressure' => [
            'rx' => true,
            'en' => 'This is a blood-pressure medicine, taken long term to keep blood pressure controlled.',
            'bn' => 'এটি রক্তচাপের ওষুধ, রক্তচাপ নিয়ন্ত্রণে রাখতে দীর্ঘমেয়াদে খেতে হয়।',
            'cautions_en' => ['Keep taking it even when you feel fine — high blood pressure usually has no symptoms.', 'Never stop or change the dose on your own.', 'Tell your doctor if you are pregnant or planning to be.'],
            'cautions_bn' => ['ভালো লাগলেও খাওয়া চালিয়ে যান — উচ্চ রক্তচাপে সাধারণত লক্ষণ থাকে না।', 'নিজে থেকে বন্ধ বা মাত্রা পরিবর্তন করবেন না।', 'গর্ভবতী হলে বা পরিকল্পনা থাকলে চিকিৎসককে জানান।'],
        ],
        'heart_rate' => [
            'rx' => true,
            'en' => 'This medicine slows and steadies the heart, and is used for blood pressure, palpitations or heart conditions.',
            'bn' => 'এই ওষুধ হৃৎস্পন্দন ধীর ও স্থিতিশীল করে; রক্তচাপ, বুক ধড়ফড় বা হৃদরোগে ব্যবহার হয়।',
            'cautions_en' => ['Never stop it suddenly — that can be dangerous.', 'Tell your doctor if you have asthma.'],
            'cautions_bn' => ['হঠাৎ বন্ধ করবেন না — এটি বিপজ্জনক হতে পারে।', 'হাঁপানি থাকলে চিকিৎসককে জানান।'],
        ],
        'cholesterol' => [
            'rx' => true,
            'en' => 'This medicine lowers cholesterol, to reduce the long-term risk of heart attack and stroke.',
            'bn' => 'এই ওষুধ কোলেস্টেরল কমায়, যাতে হার্ট অ্যাটাক ও স্ট্রোকের দীর্ঘমেয়াদি ঝুঁকি কমে।',
            'cautions_en' => ['Report unexplained muscle pain or weakness to your doctor.', 'Not for use in pregnancy.'],
            'cautions_bn' => ['অকারণে মাংসপেশিতে ব্যথা বা দুর্বলতা হলে চিকিৎসককে জানান।', 'গর্ভাবস্থায় ব্যবহারের জন্য নয়।'],
        ],
        'diabetes' => [
            'rx' => true,
            'en' => 'This is a diabetes medicine, taken long term to keep blood sugar controlled.',
            'bn' => 'এটি ডায়াবেটিসের ওষুধ, রক্তের চিনি নিয়ন্ত্রণে রাখতে দীর্ঘমেয়াদে খেতে হয়।',
            'cautions_en' => ['Do not skip meals after taking it — blood sugar can drop too low.', 'Learn the signs of low sugar: shaking, sweating, confusion.', 'Dose changes are for your doctor to make.'],
            'cautions_bn' => ['খাওয়ার পর ওষুধ খাবেন, খাবার বাদ দেবেন না — চিনি খুব কমে যেতে পারে।', 'চিনি কমার লক্ষণ জানুন: কাঁপুনি, ঘাম, বিভ্রান্তি।', 'মাত্রা পরিবর্তন চিকিৎসকই করবেন।'],
        ],
        'painkiller_nsaid' => [
            'rx' => false,
            'en' => 'This is an anti-inflammatory painkiller, used for pain, swelling and fever.',
            'bn' => 'এটি প্রদাহনাশক ব্যথার ওষুধ — ব্যথা, ফোলা ও জ্বরে ব্যবহার হয়।',
            'cautions_en' => ['Take it after food — on an empty stomach it can cause gastric pain or ulcers.', 'Avoid during dengue or unexplained fever: it can increase bleeding risk.', 'Avoid if you have a stomach ulcer or kidney disease.'],
            'cautions_bn' => ['খাবারের পর খাবেন — খালি পেটে গ্যাস্ট্রিক বা আলসার হতে পারে।', 'ডেঙ্গু বা অজানা জ্বরে এড়িয়ে চলুন: রক্তক্ষরণের ঝুঁকি বাড়ে।', 'পেটে আলসার বা কিডনির রোগ থাকলে এড়িয়ে চলুন।'],
        ],
        'painkiller_basic' => [
            'rx' => false,
            'en' => 'This medicine brings down fever and eases mild to moderate pain.',
            'bn' => 'এই ওষুধ জ্বর কমায় এবং হালকা থেকে মাঝারি ব্যথা কমায়।',
            'cautions_en' => ['Never exceed the daily amount on the packet — too much damages the liver.', 'Many cold and flu products already contain it; do not double up.'],
            'cautions_bn' => ['প্যাকেটে লেখা দৈনিক মাত্রার বেশি নয় — বেশি হলে লিভার ক্ষতিগ্রস্ত হয়।', 'অনেক সর্দি-জ্বরের ওষুধেই এটি থাকে; একসাথে দুটি খাবেন না।'],
        ],
        'painkiller_strong' => [
            'rx' => true,
            'en' => 'This is a strong prescription painkiller.',
            'bn' => 'এটি শক্তিশালী ব্যথার ওষুধ, যা প্রেসক্রিপশন ছাড়া নয়।',
            'cautions_en' => ['Only take it if a doctor prescribed it for you.', 'It can cause drowsiness — do not drive.', 'It can be habit-forming; do not take it longer than advised.'],
            'cautions_bn' => ['চিকিৎসক লিখে দিলে তবেই খাবেন।', 'ঘুম ঘুম লাগতে পারে — গাড়ি চালাবেন না।', 'আসক্তি তৈরি করতে পারে; নির্দেশের চেয়ে বেশি দিন খাবেন না।'],
        ],
        'antihistamine' => [
            'rx' => false,
            'en' => 'This is an antihistamine, used for allergy symptoms such as itching, rash, sneezing and runny nose.',
            'bn' => 'এটি অ্যান্টিহিস্টামিন — চুলকানি, র‍্যাশ, হাঁচি ও নাক দিয়ে পানি পড়ার মতো অ্যালার্জির লক্ষণে ব্যবহার হয়।',
            'cautions_en' => ['Some of these make you sleepy — avoid driving until you know how it affects you.', 'Swelling of the lips or face with breathing trouble is an emergency, not a tablet problem.'],
            'cautions_bn' => ['কিছু অ্যান্টিহিস্টামিনে ঘুম আসে — প্রভাব না বোঝা পর্যন্ত গাড়ি চালাবেন না।', 'ঠোঁট বা মুখ ফুলে শ্বাসকষ্ট হলে সেটি জরুরি অবস্থা, ট্যাবলেটে হবে না।'],
        ],
        'asthma_inhaler' => [
            'rx' => true,
            'en' => 'This medicine opens the airways and is used for asthma and breathing difficulty.',
            'bn' => 'এই ওষুধ শ্বাসনালী খুলে দেয়; হাঁপানি ও শ্বাসকষ্টে ব্যবহার হয়।',
            'cautions_en' => ['Use it exactly as your doctor showed you.', 'If you need it far more often than usual, see a doctor.', 'Severe breathlessness needs emergency care — call 999.'],
            'cautions_bn' => ['চিকিৎসক যেভাবে দেখিয়েছেন ঠিক সেভাবে ব্যবহার করুন।', 'স্বাভাবিকের চেয়ে অনেক বেশি লাগলে চিকিৎসক দেখান।', 'তীব্র শ্বাসকষ্টে জরুরি চিকিৎসা নিন — ৯৯৯-এ কল করুন।'],
        ],
        'asthma_preventer' => [
            'rx' => true,
            'en' => 'This medicine helps prevent asthma and allergy symptoms when taken regularly.',
            'bn' => 'নিয়মিত খেলে এই ওষুধ হাঁপানি ও অ্যালার্জির লক্ষণ প্রতিরোধে সাহায্য করে।',
            'cautions_en' => ['It prevents attacks; it does not relieve one that is happening.', 'Keep taking it as prescribed, even on good days.'],
            'cautions_bn' => ['এটি আক্রমণ প্রতিরোধ করে; চলমান আক্রমণ থামায় না।', 'ভালো থাকলেও নির্দেশমতো খাওয়া চালিয়ে যান।'],
        ],
        'anti_nausea' => [
            'rx' => false,
            'en' => 'This medicine reduces nausea and vomiting.',
            'bn' => 'এই ওষুধ বমি ভাব ও বমি কমায়।',
            'cautions_en' => ['Keep sipping fluids or ORS — stopping vomiting does not replace lost water.', 'Blood in vomit or severe belly pain needs a doctor.'],
            'cautions_bn' => ['তরল বা ওরস্যালাইন খেতে থাকুন — বমি বন্ধ হলেই হারানো পানি পূরণ হয় না।', 'বমিতে রক্ত বা পেটে তীব্র ব্যথা হলে চিকিৎসক দেখান।'],
        ],
        'ulcer_protect' => [
            'rx' => true,
            'en' => 'This medicine protects the stomach lining.',
            'bn' => 'এই ওষুধ পাকস্থলীর আবরণ রক্ষা করে।',
            'cautions_en' => ['Not safe in pregnancy — tell your doctor if you may be pregnant.'],
            'cautions_bn' => ['গর্ভাবস্থায় নিরাপদ নয় — গর্ভবতী হতে পারেন মনে হলে চিকিৎসককে জানান।'],
        ],
        'sedative' => [
            'rx' => true,
            'en' => 'This is a sedative or anti-anxiety medicine.',
            'bn' => 'এটি ঘুম বা দুশ্চিন্তা কমানোর ওষুধ।',
            'cautions_en' => ['Prescription only, and habit-forming — never take someone else’s.', 'Do not drive after taking it.', 'Do not stop suddenly after long use; ask your doctor.'],
            'cautions_bn' => ['শুধু প্রেসক্রিপশনে, এবং আসক্তি তৈরি করে — অন্যের ওষুধ খাবেন না।', 'খাওয়ার পর গাড়ি চালাবেন না।', 'দীর্ঘদিন খাওয়ার পর হঠাৎ বন্ধ করবেন না; চিকিৎসকের পরামর্শ নিন।'],
        ],
        'antiviral' => [
            'rx' => true,
            'en' => 'This is an antiviral medicine, used against specific virus infections.',
            'bn' => 'এটি অ্যান্টিভাইরাল ওষুধ — নির্দিষ্ট ভাইরাস সংক্রমণে ব্যবহার হয়।',
            'cautions_en' => ['Take it exactly as prescribed and finish the course.', 'It does not treat ordinary colds.'],
            'cautions_bn' => ['ঠিক যেভাবে লেখা আছে সেভাবে খান এবং কোর্স শেষ করুন।', 'সাধারণ সর্দিতে এটি কাজ করে না।'],
        ],
        'supplement' => [
            'rx' => false,
            'en' => 'This is a vitamin or mineral supplement.',
            'bn' => 'এটি ভিটামিন বা মিনারেল সাপ্লিমেন্ট।',
            'cautions_en' => ['A supplement does not replace proper meals.', 'Do not take several vitamin products together.'],
            'cautions_bn' => ['সাপ্লিমেন্ট সুষম খাবারের বিকল্প নয়।', 'একসাথে কয়েকটি ভিটামিন পণ্য খাবেন না।'],
        ],
        'rehydration' => [
            'rx' => false,
            'en' => 'This replaces water and salts lost through diarrhoea, vomiting or heavy sweating.',
            'bn' => 'ডায়রিয়া, বমি বা অতিরিক্ত ঘামে হারানো পানি ও লবণ পূরণ করে।',
            'cautions_en' => ['Mix with the amount of clean water written on the packet — never stronger.', 'Blood in the stool, or a child staying weak, needs a doctor.'],
            'cautions_bn' => ['প্যাকেটে লেখা পরিমাণ পরিষ্কার পানিতে মেশান — কখনও ঘন নয়।', 'পায়খানায় রক্ত বা শিশু দুর্বল থাকলে চিকিৎসক দেখান।'],
        ],
    ];

    /**
     * @return array{drug_class: string, uses_en: string, uses_bn: string, cautions_en: array, cautions_bn: array, is_prescription_only: bool}|null
     */
    public function guess(string $genericName): ?array
    {
        $name = mb_strtolower($genericName);

        foreach (self::STEMS as $stem => $class) {
            if (str_contains($name, $stem)) {
                $info = self::CLASSES[$class];

                return [
                    'drug_class' => $class,
                    'uses_en' => $info['en'],
                    'uses_bn' => $info['bn'],
                    'cautions_en' => $info['cautions_en'],
                    'cautions_bn' => $info['cautions_bn'],
                    'is_prescription_only' => $info['rx'],
                ];
            }
        }

        return null;
    }
}

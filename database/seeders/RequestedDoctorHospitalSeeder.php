<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Doctor;
use App\Models\DoctorAvailabilityTemplate;
use App\Models\DoctorHospitalAssignment;
use App\Models\Hospital;
use App\Models\HospitalFacility;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** Local demonstration accounts; rerunning does not reset existing passwords. */
class RequestedDoctorHospitalSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $names = ['Sifatullah', 'Samiul Hoque', 'Omar Bin Abdur Rob',
                'Abul Hasnat Muhammad Ishtiaqullah', 'Meherab Hasan', 'Tanvir Azad Mahir',
                'Shariful Islam Labib', 'Afia Ibnat Anika', 'Farin Ferdous',
                'Mashfique Hoque', 'T. M. Alam Dipu', 'Zahidul Islam', 'Israt Jahan'];
            $hospitalData = [
                ['Dhaka Horizon Hospital', 'Dhaka', 'Dhanmondi'],
                ['Uttara Community Hospital', 'Dhaka', 'Uttara'],
                ['Chattogram Harbour Hospital', 'Chattogram', 'Agrabad'],
                ['Sylhet Valley Hospital', 'Sylhet', 'Amberkhana'],
                ['Rajshahi Community Hospital', 'Rajshahi', 'Shaheb Bazar'],
                ['Khulna Riverside Hospital', 'Khulna', 'Sonadanga'],
                ['Barishal Community Hospital', 'Barishal', 'Sadar Road'],
                ['Rangpur Northern Hospital', 'Rangpur', 'Dhap'],
                ['Mymensingh Community Hospital', 'Mymensingh', 'Charpara'],
                ['Cumilla Community Hospital', 'Cumilla', 'Kandirpar'],
            ];
            $hospitals = [];
            foreach ($hospitalData as $i => [$name, $city, $area]) {
                $account = $this->account('hospital', Str::slug($name). '@example.com');
                $hospitals[] = Hospital::firstOrCreate(['account_id' => $account->account_id], [
                    'hospital_name' => $name, 'registration_number' => 'DEMO-REQUESTED-'.($i + 1),
                    'city' => $city, 'address' => $area.', '.$city,
                ]);
            }
            $doctors = [];
            foreach ($names as $i => $name) {
                $account = $this->account('doctor', 'dr.'.Str::slug($name, '.').'@example.com');
                $doctor = Doctor::firstOrCreate(['account_id' => $account->account_id], [
                    'full_name' => $name, 'age' => 35 + $i, 'blood_group' => 'O+',
                    'consultation_fee' => 600 + ($i % 5) * 100,
                    'bio' => 'Demo doctor profile for local project testing.',
                    'verification_status' => 'approved',
                ]);
                $doctors[] = $doctor;
                $hospital = $hospitals[$i % count($hospitals)];
                DoctorHospitalAssignment::firstOrCreate([
                    'doctor_id' => $doctor->doctor_id, 'hospital_id' => $hospital->hospital_id,
                ], ['status' => 'active']);
                // Sunday through Thursday; separate morning onsite and evening online sessions.
                foreach ([0, 1, 2, 3, 4] as $day) {
                    foreach (['onsite' => ['09:00:00', '12:00:00'], 'online' => ['18:00:00', '20:00:00']] as $mode => [$start, $end]) {
                        DoctorAvailabilityTemplate::firstOrCreate([
                            'doctor_id' => $doctor->doctor_id, 'day_of_week' => $day,
                            'hospital_id' => $mode === 'onsite' ? $hospital->hospital_id : null,
                            'mode' => $mode, 'start_time' => $start, 'end_time' => $end,
                        ], ['max_patients' => 15, 'is_active' => true]);
                    }
                }
            }
            $specialties = ['General Physician', 'Cardiology', 'Dermatology', 'Pediatrics',
                'Gynecology', 'Orthopedics', 'Neurology', 'Psychiatry', 'ENT', 'Ophthalmology',
                'Gastroenterology', 'Endocrinology', 'Urology', 'Pulmonology', 'Dentistry'];
            foreach ($specialties as $name) {
                if (!DB::table('specialties')->where('specialty_name', $name)->exists()) {
                    DB::table('specialties')->insert(['specialty_name' => $name]);
                }
            }
            // Include any additional specialties already configured in this installation.
            foreach (DB::table('specialties')->orderBy('specialty_id')->get() as $i => $specialty) {
                foreach ([$i % count($doctors), ($i + 1) % count($doctors)] as $index) {
                    $doctors[$index]->specialties()->syncWithoutDetaching([$specialty->specialty_id]);
                }
            }
            $categoryId = DB::table('facility_categories')->where('category_name', 'Diagnostic Test')->value('category_id');
            $categoryId ??= DB::table('facility_categories')->insertGetId(['category_name' => 'Diagnostic Test']);
            $tests = ['Complete Blood Count (CBC)' => 400, 'Blood Sugar Test' => 150,
                'Lipid Profile' => 900, 'HbA1c' => 1000, 'Thyroid Stimulating Hormone (TSH)' => 800,
                'Liver Function Test' => 1200, 'Serum Creatinine' => 400,
                'Urine Routine Examination' => 250, 'Dengue NS1 Antigen' => 700,
                'Electrolyte Panel' => 1000, 'C-Reactive Protein (CRP)' => 600,
                'Blood Group and Rh Typing' => 200];
            foreach ($tests as $name => $price) {
                $typeId = DB::table('facility_types')->where('name', $name)->value('facility_type_id');
                $typeId ??= DB::table('facility_types')->insertGetId([
                    'category_id' => $categoryId, 'name' => $name,
                    'unit_label' => 'per test', 'is_occupancy' => false,
                ]);
                foreach ($hospitals as $i => $hospital) {
                    HospitalFacility::firstOrCreate([
                        'hospital_id' => $hospital->hospital_id, 'facility_type_id' => $typeId,
                    ], ['price' => $price + $i * 20, 'daily_capacity' => 30]);
                }
            }
        });
        $this->command->info('Created requested demo roster, weekly availability, specialty coverage and hospital tests. New account password: 12345678');
    }

    private function account(string $role, string $email): Account
    {
        $account = Account::firstOrCreate(['email' => $email], [
            'role' => $role, 'password_hash' => Hash::make('12345678'),
            'is_verified' => true, 'is_active' => true,
        ]);
        if ($account->role !== $role) {
            throw new \RuntimeException('Existing account has a different role: '.$email);
        }
        return $account;
    }
}

<?php

use App\Http\Controllers\AdminAnalyticsController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AllergyController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\BloodDonationController;
use App\Http\Controllers\BloodRequestController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeliveryOrderController;
use App\Http\Controllers\DoctorAnalyticsController;
use App\Http\Controllers\DoctorAvailabilityController;
use App\Http\Controllers\FacilityBookingController;
use App\Http\Controllers\FacilityComparisonController;
use App\Http\Controllers\FaqController;
use App\Http\Controllers\ForgotPasswordController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\HospitalDoctorController;
use App\Http\Controllers\HospitalFacilityController;
use App\Http\Controllers\HospitalPaymentMethodController;
use App\Http\Controllers\HospitalStatsController;
use App\Http\Controllers\InboxController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\MedicalRecordController;
use App\Http\Controllers\MedicineComparisonController;
use App\Http\Controllers\MedicineOrderController;
use App\Http\Controllers\MedicineReminderController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OperationRequestController;
use App\Http\Controllers\OtpController;
use App\Http\Controllers\PharmacyInventoryController;
use App\Http\Controllers\PharmacyOrderController;
use App\Http\Controllers\PrescriptionController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegistrationController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\RewardController;
use App\Http\Controllers\SymptomCheckerController;
use App\Http\Controllers\AssistantController;
use App\Http\Controllers\VitalController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/locale/{locale}', [LocaleController::class, 'switch'])->name('locale.switch');

Route::middleware('guest')->group(function () {
    Route::get('/register', [RegistrationController::class, 'choose'])->name('register.choose');

    Route::get('/register/patient', [RegistrationController::class, 'showPatient'])->name('register.patient');
    Route::post('/register/patient', [RegistrationController::class, 'storePatient']);

    Route::get('/register/doctor', [RegistrationController::class, 'showDoctor'])->name('register.doctor');
    Route::post('/register/doctor', [RegistrationController::class, 'storeDoctor']);

    Route::get('/register/hospital', [RegistrationController::class, 'showHospital'])->name('register.hospital');
    Route::post('/register/hospital', [RegistrationController::class, 'storeHospital']);

    Route::get('/register/pharmacy', [RegistrationController::class, 'showPharmacy'])->name('register.pharmacy');
    Route::post('/register/pharmacy', [RegistrationController::class, 'storePharmacy']);

    Route::get('/register/delivery', [RegistrationController::class, 'showDelivery'])->name('register.delivery');
    Route::post('/register/delivery', [RegistrationController::class, 'storeDelivery']);

    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    Route::get('/forgot-password', [ForgotPasswordController::class, 'show'])->name('password.forgot');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'send'])->name('password.forgot.send');
});

Route::get('/verify-otp', [OtpController::class, 'show'])->name('otp.show');
Route::post('/verify-otp', [OtpController::class, 'verify'])->name('otp.verify');
Route::post('/verify-otp/resend', [OtpController::class, 'resend'])->name('otp.resend');

// Not wrapped in the 'guest' middleware group, same reasoning as
// verify-otp above: gating is done by checking session state
// (password_reset_account_id), not by auth status.
Route::get('/reset-password', [ForgotPasswordController::class, 'showReset'])->name('password.reset.show');
Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])->name('password.reset');

Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

Route::middleware(['auth', 'role:patient'])->prefix('patient')->name('patient.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'patient'])->name('dashboard');
    Route::post('/dashboard/tools', [DashboardController::class, 'updatePatientTools'])->name('dashboard.tools.update');
    Route::get('/rewards', [RewardController::class, 'index'])->name('rewards');

    Route::get('/doctors', [AppointmentController::class, 'searchDoctors'])->name('doctors');
    Route::get('/doctors/{doctor}', [AppointmentController::class, 'showDoctor'])->name('doctors.show');
    Route::post('/doctors/{doctor}/book', [AppointmentController::class, 'book'])->name('doctors.book');
    Route::post('/doctors/{doctor}/waitlist', [AppointmentController::class, 'joinWaitlist'])->name('doctors.waitlist');
    Route::post('/doctors/{doctor}/favorite', [AppointmentController::class, 'toggleFavorite'])->name('doctors.favorite');
    Route::get('/favorites', [AppointmentController::class, 'favorites'])->name('favorites');
    Route::get('/appointments', [AppointmentController::class, 'myAppointments'])->name('appointments');
    Route::post('/appointments/{appointment}/cancel', [AppointmentController::class, 'cancel'])->name('appointments.cancel');
    Route::post('/waitlist/{waitlist}/leave', [AppointmentController::class, 'leaveWaitlist'])->name('waitlist.leave');

    Route::get('/facilities', [FacilityComparisonController::class, 'index'])->name('facilities');
    Route::get('/lab-tests', [FacilityComparisonController::class, 'labTests'])->name('lab-tests');
    Route::get('/hospitals/{hospital}', [FacilityComparisonController::class, 'showHospital'])->name('hospitals.show');
    Route::get('/facilities/{offering}/book', [FacilityBookingController::class, 'show'])->name('facilities.book');
    Route::post('/facilities/{offering}/book', [FacilityBookingController::class, 'book'])->name('facilities.book.store');
    Route::get('/facility-bookings', [FacilityBookingController::class, 'myBookings'])->name('facility-bookings');

    Route::get('/medicine', [MedicineComparisonController::class, 'index'])->name('medicine');

    Route::get('/cart', [CartController::class, 'show'])->name('cart');
    Route::post('/cart/add', [CartController::class, 'add'])->name('cart.add');
    Route::post('/cart/remove', [CartController::class, 'remove'])->name('cart.remove');
    Route::post('/cart/clear', [CartController::class, 'clear'])->name('cart.clear');

    Route::get('/checkout', [MedicineOrderController::class, 'checkout'])->name('checkout');
    Route::post('/checkout', [MedicineOrderController::class, 'store'])->name('checkout.store');
    Route::get('/orders', [MedicineOrderController::class, 'myOrders'])->name('orders');

    Route::get('/prescriptions', [PrescriptionController::class, 'myPrescriptions'])->name('prescriptions');
    Route::post('/prescriptions/{item}/reminders', [MedicineReminderController::class, 'store'])->name('reminders.store');
    Route::post('/reminders/{reminder}/delete', [MedicineReminderController::class, 'destroy'])->name('reminders.destroy');

    Route::get('/records', [MedicalRecordController::class, 'index'])->name('records');
    Route::get('/records/print', [MedicalRecordController::class, 'patientPrint'])->name('records.print');
    Route::get('/records/create', [MedicalRecordController::class, 'create'])->name('records.create');
    Route::post('/records', [MedicalRecordController::class, 'store'])->name('records.store');
    Route::post('/records/health-profile', [MedicalRecordController::class, 'updateHealthProfile'])->name('records.health-profile.update');
    Route::post('/records/grants/{grant}/approve', [MedicalRecordController::class, 'approveGrant'])->name('records.grants.approve');
    Route::post('/records/grants/{grant}/deny', [MedicalRecordController::class, 'denyGrant'])->name('records.grants.deny');

    Route::get('/vitals', [VitalController::class, 'index'])->name('vitals');

    Route::get('/allergies/create', [AllergyController::class, 'create'])->name('allergies.create');
    Route::post('/allergies', [AllergyController::class, 'store'])->name('allergies.store');
    Route::post('/allergies/{allergy}/remove', [AllergyController::class, 'destroy'])->name('allergies.destroy');

    // /reviews/rate registered BEFORE nothing wildcard-shaped follows it
    // here, but kept in this order anyway to match the site's established
    // convention (see InboxController's routes) of literal segments before
    // any {param}-style route under the same prefix.
    Route::get('/reviews', [ReviewController::class, 'index'])->name('reviews');
    Route::get('/reviews/rate', [ReviewController::class, 'create'])->name('reviews.rate');
    Route::post('/reviews/rate', [ReviewController::class, 'store'])->name('reviews.rate.store');

    Route::get('/operations', [OperationRequestController::class, 'index'])->name('operations');
    Route::get('/operations/create/{offering}', [OperationRequestController::class, 'create'])->name('operations.create');
    Route::post('/operations/create/{offering}', [OperationRequestController::class, 'store'])->name('operations.store');
    Route::get('/operations/{operationRequest}/accept', [OperationRequestController::class, 'showAccept'])->name('operations.accept.show');
    Route::post('/operations/{operationRequest}/accept', [OperationRequestController::class, 'accept'])->name('operations.accept');
    Route::post('/operations/{operationRequest}/decline', [OperationRequestController::class, 'decline'])->name('operations.decline');
    Route::post('/operations/{operationRequest}/cancel', [OperationRequestController::class, 'cancel'])->name('operations.cancel');

    Route::get('/symptom-checker', [SymptomCheckerController::class, 'index'])->name('symptom-checker');
    Route::post('/symptom-checker', [SymptomCheckerController::class, 'search'])->name('symptom-checker.search');

    Route::get('/assistant', [AssistantController::class, 'index'])->name('assistant');

    Route::get('/blood-donations', [BloodDonationController::class, 'index'])->name('blood-donations');
    Route::get('/blood-donations/create', [BloodDonationController::class, 'create'])->name('blood-donations.create');
    Route::post('/blood-donations', [BloodDonationController::class, 'store'])->name('blood-donations.store');
});

Route::middleware(['auth', 'role:doctor'])->prefix('doctor')->name('doctor.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'doctor'])->name('dashboard');

    Route::get('/availability', [DoctorAvailabilityController::class, 'index'])->name('availability');
    Route::get('/availability/create', [DoctorAvailabilityController::class, 'create'])->name('availability.create');
    Route::post('/availability', [DoctorAvailabilityController::class, 'store'])->name('availability.store');
    Route::delete('/availability/{template}', [DoctorAvailabilityController::class, 'destroy'])->name('availability.destroy');

    Route::get('/leave', [DoctorAvailabilityController::class, 'leaveIndex'])->name('leave');
    Route::post('/leave', [DoctorAvailabilityController::class, 'storeLeave'])->name('leave.store');
    Route::post('/leave/{leaveDate}/remove', [DoctorAvailabilityController::class, 'destroyLeave'])->name('leave.destroy');

    Route::get('/appointments', [AppointmentController::class, 'doctorAppointments'])->name('appointments');
    Route::post('/appointments/{appointment}/visited', [AppointmentController::class, 'markVisited'])->name('appointments.visited');
    Route::post('/queue-status', [AppointmentController::class, 'updateQueueStatus'])->name('queue-status.update');

    Route::get('/appointments/{appointment}/prescription', [PrescriptionController::class, 'create'])->name('appointments.prescription.create');
    Route::post('/appointments/{appointment}/prescription', [PrescriptionController::class, 'store'])->name('appointments.prescription.store');
    Route::post('/appointments/{appointment}/prescription/add-item', [PrescriptionController::class, 'addItem'])->name('appointments.prescription.add-item');
    Route::post('/appointments/{appointment}/prescription/remove-item', [PrescriptionController::class, 'removeItem'])->name('appointments.prescription.remove-item');
    Route::post('/appointments/{appointment}/prescription/add-facility-item', [PrescriptionController::class, 'addFacilityItem'])->name('appointments.prescription.add-facility-item');
    Route::post('/appointments/{appointment}/prescription/remove-facility-item', [PrescriptionController::class, 'removeFacilityItem'])->name('appointments.prescription.remove-facility-item');

    Route::get('/appointments/{appointment}/vitals', [VitalController::class, 'create'])->name('appointments.vitals.create');
    Route::post('/appointments/{appointment}/vitals', [VitalController::class, 'store'])->name('appointments.vitals.store');

    Route::get('/records', [MedicalRecordController::class, 'patients'])->name('records');
    Route::post('/records/{patient}/request', [MedicalRecordController::class, 'requestAccess'])->name('records.request');
    Route::get('/records/{patient}', [MedicalRecordController::class, 'viewPatientRecords'])->name('records.show');
    Route::get('/records/{patient}/print', [MedicalRecordController::class, 'doctorPrintPatient'])->name('records.print');

    Route::get('/reviews', [ReviewController::class, 'forDoctor'])->name('reviews');
    Route::get('/analytics', [DoctorAnalyticsController::class, 'index'])->name('analytics');
});

// Shared by both patients and doctors — a route only one specific patient
// and one specific doctor may open (checked inside the controller itself,
// since which two people are allowed depends on the appointment, not the role).
Route::middleware('auth')->group(function () {
    Route::get('/consultation/{appointment}', [ConsultationController::class, 'show'])->name('consultation.show');
    Route::post('/consultation/{appointment}/signal', [ConsultationController::class, 'postSignal'])->name('consultation.signal');
    Route::get('/consultation/{appointment}/poll', [ConsultationController::class, 'pollSignals'])->name('consultation.poll');
    Route::post('/consultation/{appointment}/chat', [ConsultationController::class, 'sendMessage'])->name('consultation.chat.send');
    Route::get('/consultation/{appointment}/chat/poll', [ConsultationController::class, 'pollMessages'])->name('consultation.chat.poll');

    Route::get('/records/{record}/download', [MedicalRecordController::class, 'download'])->name('records.download');
    Route::get('/prescriptions/{prescription}', [PrescriptionController::class, 'show'])->name('prescriptions.show');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.photo.update');
    Route::post('/profile/photo/remove', [ProfileController::class, 'destroyPhoto'])->name('profile.photo.destroy');

    Route::get('/report', [ReportController::class, 'index'])->name('report.index');
    Route::get('/report/create', [ReportController::class, 'create'])->name('report.create');
    Route::post('/report', [ReportController::class, 'store'])->name('report.store');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');

    Route::get('/help', [FaqController::class, 'index'])->name('help.index');
    Route::post('/help', [FaqController::class, 'search'])->name('help.search');

    Route::get('/inbox', [InboxController::class, 'index'])->name('inbox.index');
    Route::get('/inbox/create', [InboxController::class, 'create'])->name('inbox.create');
    Route::post('/inbox/start', [InboxController::class, 'store'])->name('inbox.start');
    Route::get('/inbox/{conversation}', [InboxController::class, 'show'])->name('inbox.show');
    Route::post('/inbox/{conversation}/send', [InboxController::class, 'sendMessage'])->name('inbox.send');
    Route::get('/inbox/{conversation}/poll', [InboxController::class, 'pollMessages'])->name('inbox.poll');
});

Route::middleware(['auth', 'role:hospital'])->prefix('hospital')->name('hospital.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'hospital'])->name('dashboard');

    Route::get('/doctors', [HospitalDoctorController::class, 'index'])->name('doctors');
    Route::get('/doctors/create', [HospitalDoctorController::class, 'create'])->name('doctors.create');
    Route::post('/doctors/{doctor}/assign', [HospitalDoctorController::class, 'assign'])->name('doctors.assign');
    Route::post('/doctors/{doctor}/revoke', [HospitalDoctorController::class, 'revoke'])->name('doctors.revoke');

    Route::get('/facilities', [HospitalFacilityController::class, 'index'])->name('facilities');
    Route::get('/facilities/types/create', [HospitalFacilityController::class, 'createType'])->name('facilities.types.create');
    Route::post('/facilities/types', [HospitalFacilityController::class, 'storeType'])->name('facilities.types.store');
    Route::get('/facilities/{facilityType}/edit', [HospitalFacilityController::class, 'create'])->name('facilities.create');
    Route::post('/facilities', [HospitalFacilityController::class, 'store'])->name('facilities.store');
    Route::post('/facilities/{offering}/remove', [HospitalFacilityController::class, 'destroy'])->name('facilities.destroy');
    Route::get('/facility-bookings', [HospitalFacilityController::class, 'bookings'])->name('facility-bookings');
    Route::post('/facility-bookings/{booking}/complete', [HospitalFacilityController::class, 'markCompleted'])->name('facility-bookings.complete');

    Route::get('/payment-methods', [HospitalPaymentMethodController::class, 'index'])->name('payment-methods');
    Route::post('/payment-methods', [HospitalPaymentMethodController::class, 'update'])->name('payment-methods.update');

    Route::get('/appointment-stats', [HospitalStatsController::class, 'index'])->name('appointment-stats');

    Route::get('/reviews', [ReviewController::class, 'forHospital'])->name('reviews');

    Route::get('/operations', [OperationRequestController::class, 'hospitalIndex'])->name('operations');
    Route::get('/operations/{operationRequest}/offer', [OperationRequestController::class, 'offerForm'])->name('operations.offer.show');
    Route::post('/operations/{operationRequest}/offer', [OperationRequestController::class, 'storeOffer'])->name('operations.offer');
    Route::post('/operations/{operationRequest}/reprioritize', [OperationRequestController::class, 'reprioritize'])->name('operations.reprioritize');
    Route::post('/operations/{operationRequest}/complete', [OperationRequestController::class, 'markCompleted'])->name('operations.complete');

    Route::get('/blood-requests', [BloodRequestController::class, 'index'])->name('blood-requests');
    Route::get('/blood-requests/create', [BloodRequestController::class, 'create'])->name('blood-requests.create');
    Route::post('/blood-requests', [BloodRequestController::class, 'store'])->name('blood-requests.store');
});

Route::middleware(['auth', 'role:pharmacy'])->prefix('pharmacy')->name('pharmacy.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'pharmacy'])->name('dashboard');

    Route::get('/inventory', [PharmacyInventoryController::class, 'index'])->name('inventory');
    Route::get('/inventory/create', [PharmacyInventoryController::class, 'create'])->name('inventory.create');
    Route::post('/inventory', [PharmacyInventoryController::class, 'store'])->name('inventory.store');
    Route::post('/inventory/{stock}/remove', [PharmacyInventoryController::class, 'destroy'])->name('inventory.destroy');
    Route::get('/inventory/medicines/create', [PharmacyInventoryController::class, 'createMedicine'])->name('inventory.medicines.create');
    Route::post('/inventory/medicines', [PharmacyInventoryController::class, 'storeMedicine'])->name('inventory.medicines.store');

    Route::get('/orders', [PharmacyOrderController::class, 'index'])->name('orders');
    Route::post('/orders/{order}/accept', [PharmacyOrderController::class, 'accept'])->name('orders.accept');
    Route::post('/orders/{order}/cancel', [PharmacyOrderController::class, 'cancel'])->name('orders.cancel');

    Route::get('/reviews', [ReviewController::class, 'forPharmacy'])->name('reviews');
});

Route::middleware(['auth', 'role:delivery'])->prefix('delivery')->name('delivery.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'delivery'])->name('dashboard');

    Route::get('/available', [DeliveryOrderController::class, 'available'])->name('available');
    Route::post('/orders/{order}/accept', [DeliveryOrderController::class, 'accept'])->name('orders.accept');
    Route::get('/my-deliveries', [DeliveryOrderController::class, 'myDeliveries'])->name('my-deliveries');
    Route::post('/orders/{order}/request-otp', [DeliveryOrderController::class, 'requestOtp'])->name('orders.request-otp');
    Route::post('/orders/{order}/confirm', [DeliveryOrderController::class, 'confirm'])->name('orders.confirm');

    Route::get('/reviews', [ReviewController::class, 'forDelivery'])->name('reviews');
});

Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'admin'])->name('dashboard');

    Route::get('/users', [AdminController::class, 'searchUser'])->name('users');
    Route::get('/analytics', [AdminAnalyticsController::class, 'index'])->name('analytics');

    Route::get('/transactions', [AdminController::class, 'transactions'])->name('transactions');

    Route::get('/doctor-verifications', [AdminController::class, 'doctorVerifications'])->name('doctor-verifications');
    Route::post('/doctor-verifications/{certificate}/approve', [AdminController::class, 'approveCertificate'])->name('doctor-verifications.approve');
    Route::post('/doctor-verifications/{certificate}/reject', [AdminController::class, 'rejectCertificate'])->name('doctor-verifications.reject');
    Route::get('/doctor-verifications/{certificate}/download', [AdminController::class, 'downloadCertificate'])->name('doctor-verifications.download');

    Route::get('/reports', [AdminController::class, 'reports'])->name('reports');
    Route::post('/reports/{report}/respond', [AdminController::class, 'respondToReport'])->name('reports.respond');
});

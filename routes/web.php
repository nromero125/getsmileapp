<?php

use App\Http\Controllers\Admin\ClinicController;
use App\Http\Controllers\Admin\DiagnosisCatalogController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\TreatmentController;
use App\Http\Controllers\Appointment\AppointmentController;
use App\Http\Controllers\Appointment\DentalRecordController;
use App\Http\Controllers\Appointment\DiagnosisController;
use App\Http\Controllers\Billing\InvoiceController;
use App\Http\Controllers\Billing\Dgii607Controller;
use App\Http\Controllers\Billing\QuoteController;
use App\Http\Controllers\Billing\SubscriptionController;
use App\Http\Controllers\Inventory\InventoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\Patient\PatientController;
use App\Http\Controllers\Patient\PatientFileController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn() => \Inertia\Inertia::render('Welcome'))->name('home');

// Auth routes (Breeze)
require __DIR__.'/auth.php';

// Paddle webhook (must be before auth middleware)
Route::post('/paddle/webhook', '\Laravel\Paddle\Http\Controllers\WebhookController')->name('cashier.webhook');

// WhatsApp webhook — Meta verification (GET) + incoming events (POST), no auth/CSRF
Route::get('/whatsapp/webhook',  [\App\Http\Controllers\WhatsAppWebhookController::class, 'verify'])->name('whatsapp.webhook.verify');
Route::post('/whatsapp/webhook', [\App\Http\Controllers\WhatsAppWebhookController::class, 'handle'])->name('whatsapp.webhook.handle');

// Authenticated routes
Route::middleware(['auth', 'verified'])->group(function () {

    // Subscription — no 'subscribed' middleware here (accessible during trial and after expiry)
    Route::get('/subscription/checkout', [SubscriptionController::class, 'checkout'])->name('subscription.checkout');
    Route::post('/subscription/checkout-url', [SubscriptionController::class, 'checkoutUrl'])->name('subscription.checkout-url');
    Route::get('/subscription/required', [SubscriptionController::class, 'required'])->name('subscription.required');
    Route::get('/subscription/billing-portal', [SubscriptionController::class, 'billingPortal'])->name('subscription.billing-portal');
    Route::get('/subscription/manage', [SubscriptionController::class, 'manage'])->name('subscription.manage');
    Route::patch('/subscription/whatsapp-plan', [SubscriptionController::class, 'updateWhatsAppPlan'])->name('subscription.whatsapp-plan');

    // All other authenticated routes require active subscription or trial
    Route::middleware('subscribed')->group(function () {

        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Reports
        Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');

        // DGII 607 export
        Route::get('/dgii/607', [Dgii607Controller::class, 'index'])->name('dgii.607');
        Route::get('/dgii/607/download', [Dgii607Controller::class, 'download'])
            ->middleware('can:billing')
            ->name('dgii.607.download');

        // Profile
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

        // Patients — view/create/edit: all roles; delete: billing only
        Route::resource('patients', PatientController::class)->except(['destroy']);
        Route::delete('patients/{patient}', [PatientController::class, 'destroy'])
            ->middleware('can:billing')->name('patients.destroy');
        Route::post('patients/{patient}/files', [PatientFileController::class, 'store'])->name('patients.files.store');
        Route::delete('patients/{patient}/files/{media}', [PatientFileController::class, 'destroy'])->name('patients.files.destroy');
        Route::get('patients/{patient}/files/{media}/download', [PatientFileController::class, 'download'])->name('patients.files.download');

        // Dental Records (Odontogram) — clinical only (dentist + admin)
        Route::middleware('can:clinical')->group(function () {
            Route::get('patients/{patient}/odontogram', [DiagnosisController::class, 'index'])->name('patients.odontogram');
            Route::post('patients/{patient}/dental-records', [DentalRecordController::class, 'store'])->name('patients.dental-records.store');
            Route::put('patients/{patient}/dental-records/{record}', [DentalRecordController::class, 'update'])->name('patients.dental-records.update');
            Route::delete('patients/{patient}/dental-records/{record}', [DentalRecordController::class, 'destroy'])->name('patients.dental-records.destroy');
            Route::post('patients/{patient}/diagnoses', [DiagnosisController::class, 'store'])->name('patients.diagnoses.store');
            Route::put('patients/{patient}/diagnoses/{diagnosis}', [DiagnosisController::class, 'update'])->name('patients.diagnoses.update');
            Route::delete('patients/{patient}/diagnoses/{diagnosis}', [DiagnosisController::class, 'destroy'])->name('patients.diagnoses.destroy');
        });

        // Appointments — view/create/update: all roles; delete: billing only
        Route::get('/appointments', [AppointmentController::class, 'index'])->name('appointments.index');
        Route::post('/appointments', [AppointmentController::class, 'store'])->name('appointments.store');
        Route::get('/appointments/{appointment}', [AppointmentController::class, 'show'])->name('appointments.show');
        Route::put('/appointments/{appointment}', [AppointmentController::class, 'update'])->name('appointments.update');
        Route::patch('/appointments/{appointment}', [AppointmentController::class, 'update']);
        Route::delete('/appointments/{appointment}', [AppointmentController::class, 'destroy'])
            ->middleware('can:billing')->name('appointments.destroy');
        Route::post('/appointments/{appointment}/send-confirmation', [AppointmentController::class, 'sendConfirmation'])
            ->name('appointments.send-confirmation');

        // Billing — view: all roles; create/pay: billing only (receptionist + admin)
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/receivables', [InvoiceController::class, 'receivables'])->name('receivables.index');
        Route::middleware('can:billing')->group(function () {
            Route::get('/invoices/create', [InvoiceController::class, 'create'])->name('invoices.create');
            Route::post('/invoices', [InvoiceController::class, 'store'])->name('invoices.store');
            Route::post('/invoices/{invoice}/payment', [InvoiceController::class, 'recordPayment'])->name('invoices.payment');
            Route::post('/invoices/{invoice}/installments', [InvoiceController::class, 'storeInstallmentPlan'])->name('invoices.installments.store');
            Route::post('/invoices/{invoice}/installments/{installment}/pay', [InvoiceController::class, 'payInstallment'])->name('invoices.installments.pay');
            Route::delete('/invoices/{invoice}/installments', [InvoiceController::class, 'destroyInstallmentPlan'])->name('invoices.installments.destroy');
        });
        Route::post('/invoices/{invoice}/refund', [InvoiceController::class, 'refund'])
            ->middleware('can:billing')->name('invoices.refund');
        Route::post('/invoices/{invoice}/void', [InvoiceController::class, 'void'])
            ->middleware('can:billing')->name('invoices.void');
        Route::get('/invoices/{invoice}/pdf', [InvoiceController::class, 'pdf'])->name('invoices.pdf');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])->name('invoices.show');

        // Quotes
        Route::get('/quotes', [QuoteController::class, 'index'])->name('quotes.index');
        Route::middleware('can:billing')->group(function () {
            Route::get('/quotes/create', [QuoteController::class, 'create'])->name('quotes.create');
            Route::post('/quotes', [QuoteController::class, 'store'])->name('quotes.store');
            Route::patch('/quotes/{quote}/status', [QuoteController::class, 'updateStatus'])->name('quotes.status');
            Route::post('/quotes/{quote}/convert', [QuoteController::class, 'convertToInvoice'])->name('quotes.convert');
            Route::delete('/quotes/{quote}', [QuoteController::class, 'destroy'])->name('quotes.destroy');
        });
        Route::get('/quotes/{quote}', [QuoteController::class, 'show'])->name('quotes.show');

        // Inventory — view + movements: all roles; item CRUD: admin only
        Route::get('/inventory', [InventoryController::class, 'index'])->name('inventory.index');
        Route::post('/inventory/items/{item}/movements', [InventoryController::class, 'storeMovement'])->name('inventory.movements.store');
        Route::middleware('can:admin')->group(function () {
            Route::post('/inventory/items', [InventoryController::class, 'store'])->name('inventory.store');
            Route::put('/inventory/items/{item}', [InventoryController::class, 'update'])->name('inventory.update');
            Route::delete('/inventory/items/{item}', [InventoryController::class, 'destroy'])->name('inventory.destroy');
            Route::post('/inventory/categories', [InventoryController::class, 'storeCategory'])->name('inventory.categories.store');
            Route::delete('/inventory/categories/{category}', [InventoryController::class, 'destroyCategory'])->name('inventory.categories.destroy');
        });

        // Admin
        Route::middleware('can:admin')->group(function () {
            Route::get('/clinic/settings', [ClinicController::class, 'edit'])->name('clinic.settings');
            Route::put('/clinic/settings', [ClinicController::class, 'update'])->name('clinic.settings.update');
            Route::post('/clinic/ncf-sequences', [ClinicController::class, 'storeNcfSequence'])->name('clinic.ncf-sequences.store');
            Route::put('/clinic/ncf-sequences/{sequence}', [ClinicController::class, 'updateNcfSequence'])->name('clinic.ncf-sequences.update');
            Route::delete('/clinic/ncf-sequences/{sequence}', [ClinicController::class, 'destroyNcfSequence'])->name('clinic.ncf-sequences.destroy');
            Route::get('/staff', [StaffController::class, 'index'])->name('staff.index');
            Route::post('/staff', [StaffController::class, 'store'])->name('staff.store');
            Route::put('/staff/{user}', [StaffController::class, 'update'])->name('staff.update');
            Route::delete('/staff/{user}', [StaffController::class, 'destroy'])->name('staff.destroy');
            // Treatments
            Route::get('/treatments', [TreatmentController::class, 'index'])->name('treatments.index');
            Route::post('/treatments', [TreatmentController::class, 'store'])->name('treatments.store');
            Route::put('/treatments/{treatment}', [TreatmentController::class, 'update'])->name('treatments.update');
            Route::delete('/treatments/{treatment}', [TreatmentController::class, 'destroy'])->name('treatments.destroy');
            Route::post('/treatment-categories', [TreatmentController::class, 'storeCategory'])->name('treatment-categories.store');
            Route::delete('/treatment-categories/{category}', [TreatmentController::class, 'destroyCategory'])->name('treatment-categories.destroy');
            // Diagnosis catalog
            Route::get('/diagnosis-catalog', [DiagnosisCatalogController::class, 'index'])->name('diagnosis-catalog.index');
            Route::post('/diagnosis-catalog', [DiagnosisCatalogController::class, 'store'])->name('diagnosis-catalog.store');
            Route::put('/diagnosis-catalog/{diagnosis}', [DiagnosisCatalogController::class, 'update'])->name('diagnosis-catalog.update');
            Route::delete('/diagnosis-catalog/{diagnosis}', [DiagnosisCatalogController::class, 'destroy'])->name('diagnosis-catalog.destroy');
        });
    });
});

// Public: patient appointment confirmation (no auth)
Route::get('/appointments/confirm/{token}', [AppointmentController::class, 'confirm'])->name('appointments.confirm');

// API endpoints for calendar
Route::middleware(['auth', 'verified', 'subscribed'])->prefix('api')->group(function () {
    Route::get('/appointments/calendar', [AppointmentController::class, 'apiList'])->name('api.appointments.calendar');
});

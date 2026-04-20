<?php

use App\Http\Controllers\Admin\AdminAuditController;
use App\Http\Controllers\Admin\AdminContactController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminEventController;
use App\Http\Controllers\Admin\TwoFactorController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\AuditCwvController;
use App\Http\Controllers\AuditPaymentController;
use App\Http\Controllers\AuditPdfController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\MonitoringController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

/*
 * Pages publiques
 */
Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/prestations', [PublicController::class, 'services'])->name('services');
Route::get('/contact', [PublicController::class, 'contact'])->name('contact');
Route::get('/mentions-legales', [PublicController::class, 'mentions'])->name('mentions');
Route::get('/cgv', [PublicController::class, 'cgv'])->name('cgv');

Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:contact')
    ->name('contact.store');

/*
 * Tracking client : relayé par le helper JS
 */
Route::post('/track', [TrackingController::class, 'store'])
    ->middleware('throttle:tracking')
    ->name('track');

/*
 * Audit automatisé en libre-service
 */
Route::get('/audit', [AuditController::class, 'create'])->name('audit.create');
Route::post('/audit', [AuditController::class, 'store'])
    ->middleware('throttle:audit')
    ->name('audit.store');
Route::get('/audit/{audit:uuid}', [AuditController::class, 'show'])->name('audit.show');
Route::get('/audit/{audit:uuid}/pay', [AuditPaymentController::class, 'create'])->name('audit.pay');
Route::post('/audit/{audit:uuid}/pay', [AuditPaymentController::class, 'store'])->name('audit.pay.store');

// Add-on PDF
Route::get('/audit/{audit:uuid}/pdf', [AuditPdfController::class, 'download'])->name('audit.pdf');
Route::get('/audit/{audit:uuid}/pdf/pay', [AuditPdfController::class, 'pay'])->name('audit.pdf.pay');
Route::post('/audit/{audit:uuid}/pdf/pay', [AuditPdfController::class, 'confirmPayment'])->name('audit.pdf.pay.store');

// Add-on Core Web Vitals
Route::get('/audit/{audit:uuid}/performance', [AuditCwvController::class, 'show'])->name('audit.cwv');
Route::get('/audit/{audit:uuid}/performance/pay', [AuditCwvController::class, 'pay'])->name('audit.cwv.pay');
Route::post('/audit/{audit:uuid}/performance/pay', [AuditCwvController::class, 'confirmPayment'])->name('audit.cwv.pay.store');

// Abonnement monitoring mensuel
Route::get('/monitoring', [MonitoringController::class, 'create'])->name('monitoring.create');
Route::post('/monitoring/subscribe', [MonitoringController::class, 'store'])
    ->middleware('throttle:contact')
    ->name('monitoring.store');
Route::get('/monitoring/{subscription:token}', [MonitoringController::class, 'show'])->name('monitoring.show');
Route::post('/monitoring/{subscription:token}/cancel', [MonitoringController::class, 'cancel'])->name('monitoring.cancel');

/*
 * Espace utilisateur (Breeze)
 */
Route::get('/dashboard', function () {
    return \Inertia\Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/*
 * Administration (auth + admin + 2FA)
 */
Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/2fa/setup', [TwoFactorController::class, 'setup'])->name('2fa.setup');
    Route::post('/2fa/enable', [TwoFactorController::class, 'enable'])
        ->middleware('throttle:two-factor')
        ->name('2fa.enable');
    Route::get('/2fa/challenge', [TwoFactorController::class, 'challenge'])->name('2fa.challenge');
    Route::post('/2fa/verify', [TwoFactorController::class, 'verify'])
        ->middleware('throttle:two-factor')
        ->name('2fa.verify');
    Route::post('/2fa/disable', [TwoFactorController::class, 'disable'])->name('2fa.disable');

    Route::middleware('2fa')->group(function () {
        Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');
        Route::get('/audits', [AdminAuditController::class, 'index'])->name('audits.index');
        Route::get('/audits/{audit:uuid}', [AdminAuditController::class, 'show'])->name('audits.show');
        Route::get('/messages', [AdminContactController::class, 'index'])->name('messages.index');
        Route::get('/messages/{message}', [AdminContactController::class, 'show'])->name('messages.show');
        Route::patch('/messages/{message}', [AdminContactController::class, 'update'])->name('messages.update');
        Route::get('/events', [AdminEventController::class, 'index'])->name('events.index');
    });
});

require __DIR__.'/auth.php';

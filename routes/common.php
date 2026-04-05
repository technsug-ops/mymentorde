<?php

use App\Http\Controllers\BulletinController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\Hr\HrAttendanceController;
use App\Http\Controllers\Hr\HrCertificationController;
use App\Http\Controllers\Hr\HrLeaveController;
use App\Http\Controllers\Hr\HrOnboardingController;
use App\Http\Controllers\StaffContractController;
use App\Http\Controllers\StaffDashboardController;
use Illuminate\Support\Facades\Route;

// ── Staff Dashboard ───────────────────────────────────────────────────────────
Route::middleware(['company.context', 'auth'])->group(function (): void {
    Route::get('/staff/dashboard', [StaffDashboardController::class, 'index'])->name('staff.dashboard');
});

// ── Presence & Müsaitlik ──────────────────────────────────────────────────────
Route::middleware(['company.context', 'auth'])->group(function (): void {
    Route::get('/availability',                 [\App\Http\Controllers\UserAvailabilityController::class, 'scheduleIndex'])->name('availability.index');
    Route::post('/availability/schedule',       [\App\Http\Controllers\UserAvailabilityController::class, 'scheduleSave'])->name('availability.schedule.save');
    Route::post('/availability/away',           [\App\Http\Controllers\UserAvailabilityController::class, 'awayStore'])->name('availability.away.store');
    Route::delete('/availability/away/{awayPeriod}', [\App\Http\Controllers\UserAvailabilityController::class, 'awayDelete'])->name('availability.away.delete');
    Route::get('/api/presence',                 [\App\Http\Controllers\UserAvailabilityController::class, 'presenceApi'])->name('presence.api');
    Route::post('/welcome-video/dismiss',       function (\Illuminate\Http\Request $request) {
        $portal = in_array($request->get('portal'), ['guest', 'student', 'dealer']) ? $request->get('portal') : 'guest';
        $key    = 'wv_dismissed_' . $portal . '_' . (auth()->id() ?? 0);
        session()->put($key, true);
        return response()->json(['ok' => true]);
    })->name('welcome.video.dismiss');
});

// ── DM Ek İndirme ─────────────────────────────────────────────────────────────
Route::middleware(['company.context', 'auth'])->get('/dm/attachment/{message}', [ConversationController::class, 'download'])
    ->name('dm.attachment.download');

// ── HR Self-Servis (tüm çalışanlar kendi izinlerini yönetir) ─────────────────
Route::middleware(['company.context', 'auth'])->prefix('hr/my')->group(function (): void {
    Route::get('/leaves',                                  [HrLeaveController::class, 'myLeaves'])->name('hr.my.leaves');
    Route::post('/leaves',                                 [HrLeaveController::class, 'myStore'])->name('hr.my.leaves.store');
    Route::delete('/leaves/{leave}',                       [HrLeaveController::class, 'myCancel'])->name('hr.my.leaves.cancel');
    Route::get('/leave-attachments/{attachment}/download', [HrLeaveController::class, 'downloadAttachment'])->name('hr.my.leave-attachment.download');
    Route::get('/attendance',                              [HrAttendanceController::class, 'myToday'])->name('hr.my.attendance');
    Route::get('/certifications',                          [HrCertificationController::class, 'myCertifications'])->name('hr.my.certifications');
    Route::post('/certifications',                         [HrCertificationController::class, 'myStore'])->name('hr.my.certifications.store');
    Route::put('/certifications/{cert}',                   [HrCertificationController::class, 'myUpdate'])->name('hr.my.certifications.update');
    Route::delete('/certifications/{cert}',                [HrCertificationController::class, 'myDestroy'])->name('hr.my.certifications.destroy');
    Route::get('/onboarding',                              [HrOnboardingController::class, 'myOnboarding'])->name('hr.my.onboarding');
    Route::patch('/onboarding/{task}/toggle',              [HrOnboardingController::class, 'myToggleTask'])->name('hr.my.onboarding.toggle');
});

// ── HR Devam — Giriş/Çıkış ────────────────────────────────────────────────────
Route::middleware(['company.context', 'auth'])->group(function (): void {
    Route::post('/hr/check-in',  [HrAttendanceController::class, 'checkIn'])->middleware('throttle:5,1')->name('hr.check-in');
    Route::post('/hr/check-out', [HrAttendanceController::class, 'checkOut'])->middleware('throttle:5,1')->name('hr.check-out');
});

// ── Duyuru Panosu (tüm çalışanlar okur) ──────────────────────────────────────
Route::middleware(['company.context', 'auth'])->group(function (): void {
    Route::get('/bulletins',                          [BulletinController::class, 'index'])->name('bulletins.index');
    Route::get('/bulletins/partial',                  [BulletinController::class, 'partial'])->name('bulletins.partial');
    Route::post('/bulletins/{bulletin}/read',          [BulletinController::class, 'markRead'])->name('bulletins.mark-read');
    Route::post('/bulletins/{bulletin}/react',         [BulletinController::class, 'react'])->name('bulletins.react');
});

// ── Personel Sözleşmeleri (staff kendi sözleşmelerini görür/imzalar) ──────────
Route::middleware(['company.context', 'auth'])->group(function (): void {
    Route::get('/my-contracts',                                 [StaffContractController::class, 'index'])->name('my-contracts.index');
    Route::get('/my-contracts/{contract}',                      [StaffContractController::class, 'show'])->name('my-contracts.show');
    Route::post('/my-contracts/{contract}/upload-signed',       [StaffContractController::class, 'uploadSigned'])->name('my-contracts.upload-signed');
});

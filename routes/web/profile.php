<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Profile\PhoneVerificationController;
use Illuminate\Support\Facades\Route;

// Маршруты для профиля пользователя
Route::middleware(['auth'])->prefix('profile')->group(function () {
    Route::get('/', [ProfileController::class, 'index'])->name('profile.index');
    Route::get('/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/update', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/change-password', [ProfileController::class, 'showChangePasswordForm'])->name('profile.change-password');
    Route::put('/change-password', [ProfileController::class, 'changePassword'])->name('profile.update-password');
    
    // Новые маршруты для верификации номера телефона
    Route::post('/send-phone-verification-code', [ProfileController::class, 'sendPhoneVerificationCode'])->name('profile.send-phone-verification-code');
    Route::post('/verify-phone-code', [ProfileController::class, 'verifyPhoneCode'])->name('profile.verify-phone-code');
    Route::post('/update-phone', [ProfileController::class, 'updatePhone'])->name('profile.update-phone');
    
    // Старые маршруты для обратной совместимости (если используются)
    Route::post('/phone-verification/send-code', [PhoneVerificationController::class, 'sendVerificationCode'])->name('profile.phone.send-code');
    Route::post('/phone-verification/verify', [PhoneVerificationController::class, 'verifyAndUpdatePhone'])->name('profile.phone.verify');
});

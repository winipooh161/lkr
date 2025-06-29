<?php

use App\Http\Controllers\Partner\ProjectCheckController;
use App\Http\Controllers\Partner\ProjectPhotoController;
use App\Http\Controllers\Partner\ProjectScheduleController;
use Illuminate\Support\Facades\Route;

// Маршруты для работы с проверками проекта
Route::middleware(['auth', 'partner'])->prefix('partner')->name('partner.')->group(function () {
    Route::prefix('projects/{project}/checks')->group(function () {
        Route::get('/', [ProjectCheckController::class, 'listChecks'])->name('projects.checks.list');
        Route::get('/index', [ProjectCheckController::class, 'index'])->name('projects.checks.index');
        Route::get('/{check_id}', [ProjectCheckController::class, 'show'])->name('projects.checks.show');
        Route::put('/{check_id}', [ProjectCheckController::class, 'update'])->name('projects.checks.update');
        Route::put('/{check_id}/comment', [ProjectCheckController::class, 'updateComment'])->name('projects.checks.comment');
    });
    
    // Маршруты для работы с фотоотчетом
    Route::prefix('projects/{project}/photos')->group(function () {
        Route::get('/', [ProjectPhotoController::class, 'index'])->name('projects.photos.index');
        Route::post('/', [ProjectPhotoController::class, 'store'])->name('projects.photos.store');
    });
    
    Route::delete('project-photos/{projectPhoto}', [ProjectPhotoController::class, 'destroy'])
        ->name('project-photos.destroy');
      // Маршруты для работы с графиком проектов
    Route::prefix('projects/{project}/schedule')->group(function () {
        Route::get('/file', [ProjectScheduleController::class, 'getFile'])->name('projects.schedule-file');
        Route::post('/file', [ProjectScheduleController::class, 'saveFile'])->name('projects.schedule-file.store');
        Route::post('/template', [ProjectScheduleController::class, 'createTemplate'])->name('projects.schedule-template');
        Route::post('/generate-data', [ProjectScheduleController::class, 'generateDataJson'])->name('projects.schedule-generate-data');
    });
});

// Дополнительные маршруты для проверок
Route::middleware(['auth'])->prefix('partner')->name('partner.')->group(function () {
    Route::get('/projects/{project}/checks', [ProjectCheckController::class, 'listChecks'])->name('projects.checks');
    Route::get('/projects/{project}/checks/{check_id}', [ProjectCheckController::class, 'show'])->name('projects.check.show');
    Route::post('/projects/{project}/checks/{check_id}', [ProjectCheckController::class, 'update'])->name('projects.check.update');
    Route::post('/projects/{project}/checks/{check_id}/comment', [ProjectCheckController::class, 'updateComment'])->name('projects.check.comment');
});

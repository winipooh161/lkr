<?php

use App\Http\Controllers\Partner\ProjectScheduleController;
use App\Http\Controllers\Partner\ProjectCalendarController;
use App\Http\Controllers\Partner\PdfGeneratorController;
use Illuminate\Support\Facades\Route;

// Дополнительные маршруты для партнеров
Route::middleware(['auth', 'partner'])->prefix('partner')->name('partner.')->group(function () {
    // Маршруты для работы с графиками проектов
    Route::prefix('projects/{project}/schedule')->group(function () {        Route::get('file', [ProjectScheduleController::class, 'getFile'])->name('projects.schedule-file');
        Route::post('file', [ProjectScheduleController::class, 'saveFile'])->name('projects.schedule-file.store');
        Route::post('template', [ProjectScheduleController::class, 'createTemplate'])->name('projects.schedule-template');
    });
      // Маршруты для календарного вида графика
    Route::get('projects/{project}/calendar', [ProjectCalendarController::class, 'index'])->name('projects.calendar');
    Route::get('projects/{project}/calendar-view', [ProjectCalendarController::class, 'getCalendarView'])->name('projects.calendar-view');
    
    // Маршрут для генерации PDF
    Route::post('projects/generate-pdf', [PdfGeneratorController::class, 'generatePdf'])->name('projects.generate-pdf');
});

<?php

use App\Http\Controllers\Partner\MaterialCalculatorController;
use App\Http\Controllers\Partner\ExcelTemplateController;
use Illuminate\Support\Facades\Route;

// Маршруты для работы с Excel-шаблонами
Route::middleware(['auth', 'partner'])->prefix('partner')->name('partner.')->group(function () {
    Route::prefix('excel-templates')->name('excel-templates.')->group(function () {
        // Маршрут для скачивания шаблона сметы
        Route::get('/estimate/{type}', [ExcelTemplateController::class, 'downloadEstimateTemplate'])->name('estimate');
        
        // Маршрут для получения данных о разделах и работах
        Route::get('/sections-data', [ExcelTemplateController::class, 'getSectionsData'])->name('sections-data');
    });
    
    // Маршруты для калькулятора материалов
    Route::get('/calculator', [MaterialCalculatorController::class, 'index'])->name('calculator.index');
    Route::post('/calculator/calculate', [MaterialCalculatorController::class, 'calculate'])->name('calculator.calculate');
    Route::post('/calculator/export-pdf', [MaterialCalculatorController::class, 'exportPdf'])->name('calculator.export-pdf');
    Route::post('/calculator/save-prices', [MaterialCalculatorController::class, 'savePrices'])->name('calculator.save-prices');
    Route::get('/calculator/get-prices', [MaterialCalculatorController::class, 'getPrices'])->name('calculator.get-prices');
});

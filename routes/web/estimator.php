<?php

use App\Http\Controllers\Estimator\EstimatorController;
use App\Http\Controllers\Partner\EstimateController;
use App\Http\Controllers\Partner\EstimateExcelController;
use App\Http\Controllers\Partner\EstimateItemController;
use Illuminate\Support\Facades\Route;

// Маршруты для сметчиков
Route::middleware(['auth', 'estimator'])->prefix('estimator')->name('estimator.')->group(function () {
    Route::get('/', [EstimatorController::class, 'index'])->name('dashboard');
    
    // Маршруты для управления сметами (используем те же контроллеры, что и у партнеров)
    Route::get('/estimates', [EstimateController::class, 'index'])->name('estimates.index');
    Route::get('/estimates/create', [EstimateController::class, 'create'])->name('estimates.create');
    Route::post('/estimates', [EstimateController::class, 'store'])->name('estimates.store');
    Route::get('/estimates/{estimate}', [EstimateController::class, 'show'])->name('estimates.show');
    Route::get('/estimates/{estimate}/edit', [EstimateController::class, 'edit'])->name('estimates.edit');
    Route::put('/estimates/{estimate}', [EstimateController::class, 'update'])->name('estimates.update');
    Route::delete('/estimates/{estimate}', [EstimateController::class, 'destroy'])->name('estimates.destroy');
    
    // Маршруты для Excel-файлов смет
    Route::get('estimates/{estimate}/export', [EstimateExcelController::class, 'export'])->name('estimates.export');
    Route::get('estimates/{estimate}/data', [EstimateExcelController::class, 'getData'])->name('estimates.getData');
    Route::post('estimates/{estimate}/saveExcel', [EstimateExcelController::class, 'saveExcelData'])->name('estimates.saveExcel');
    Route::post('estimates/{estimate}/upload', [EstimateExcelController::class, 'upload'])->name('estimates.upload');
    
    // Маршруты для управления элементами смет
    Route::post('estimates/{estimate}/items/add', [EstimateItemController::class, 'addRow'])->name('estimates.items.add');
    Route::put('estimates/{estimate}/items/table', [EstimateItemController::class, 'updateTable'])->name('estimates.items.table');
    
    // Маршруты для калькулятора материалов
    Route::get('/calculator', [\App\Http\Controllers\Partner\MaterialCalculatorController::class, 'index'])->name('calculator.index');
    Route::post('/calculator/calculate', [\App\Http\Controllers\Partner\MaterialCalculatorController::class, 'calculate'])->name('calculator.calculate');
    Route::post('/calculator/export-pdf', [\App\Http\Controllers\Partner\MaterialCalculatorController::class, 'exportPdf'])->name('calculator.export-pdf');
    Route::post('/calculator/save-prices', [\App\Http\Controllers\Partner\MaterialCalculatorController::class, 'savePrices'])->name('calculator.save-prices');
    Route::get('/calculator/get-prices', [\App\Http\Controllers\Partner\MaterialCalculatorController::class, 'getPrices'])->name('calculator.get-prices');
});

<?php

use App\Http\Controllers\Partner\ProjectFinanceController;
use Illuminate\Support\Facades\Route;

// Маршруты для финансовых элементов проекта
Route::middleware(['auth'])->prefix('partner')->name('partner.')->group(function () {
    // Маршруты для финансов проекта
    Route::get('projects/{project}/finance', [ProjectFinanceController::class, 'index'])
        ->name('projects.finance.index');
    Route::post('projects/{project}/finance', [ProjectFinanceController::class, 'store'])
        ->name('projects.finance.store');
    Route::get('projects-finance/{id}', [ProjectFinanceController::class, 'show'])
        ->name('projects.finance.show');
    Route::match(['put', 'post'], 'projects-finance/{id}', [ProjectFinanceController::class, 'update'])
        ->name('projects.finance.update');
    Route::delete('projects-finance/{id}', [ProjectFinanceController::class, 'destroy'])
        ->name('projects.finance.destroy');
    Route::post('projects/{project}/finance/positions', [ProjectFinanceController::class, 'updatePositions'])
        ->name('projects.finance.positions');
    Route::get('projects/{project}/finance/export', [ProjectFinanceController::class, 'export'])
        ->name('projects.finance.export');
    
    // Альтернативные маршруты для финансовых элементов (устаревшие)
    Route::get('/projects/{project}/finance-items', [ProjectFinanceController::class, 'index'])->name('projects.finance-items.index');
    Route::post('/projects/{project}/finance-items', [ProjectFinanceController::class, 'store'])->name('projects.finance-items.store');
    Route::get('/finance-items/{item}', [ProjectFinanceController::class, 'show'])->name('finance-items.show');
    Route::put('/finance-items/{item}', [ProjectFinanceController::class, 'update'])->name('finance-items.update');
    Route::delete('/finance-items/{item}', [ProjectFinanceController::class, 'destroy'])->name('finance-items.destroy');
    Route::put('/projects/{project}/finance-items/positions', [ProjectFinanceController::class, 'updatePositions'])->name('projects.finance-items.positions');
    Route::get('/projects/{project}/finance-items/export', [ProjectFinanceController::class, 'export'])->name('projects.finance-items.export');
});

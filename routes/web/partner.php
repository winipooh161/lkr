<?php

use App\Http\Controllers\Partner\PartnerController;
use App\Http\Controllers\Partner\ProjectController;
use App\Http\Controllers\Partner\ProjectFileController;
use App\Http\Controllers\Partner\EstimateController;
use App\Http\Controllers\Partner\EstimateExcelController;
use App\Http\Controllers\Partner\EstimateItemController;
use App\Http\Controllers\Partner\ExcelTemplateController;
use App\Http\Controllers\Partner\EmployeeController;
use App\Http\Controllers\Partner\ProjectCheckController;
use App\Http\Controllers\Partner\ProjectPhotoController;
use App\Http\Controllers\Partner\ProjectScheduleController;
use Illuminate\Support\Facades\Route;

// Основные маршруты для партнеров
Route::middleware(['auth', 'partner'])->prefix('partner')->name('partner.')->group(function () {
    Route::get('/', [PartnerController::class, 'index'])->name('dashboard');
    
    // Профиль партнера
    Route::get('/profile', [PartnerController::class, 'profile'])->name('profile');
    
    // Маршруты для управления объектами
    Route::resource('projects', ProjectController::class);
    
    // Маршруты для файлов проектов
    Route::prefix('projects/{project}')->group(function () {
        Route::post('/files', [ProjectFileController::class, 'store'])->name('project-files.store');
        Route::get('/files/{file}', [ProjectFileController::class, 'show'])->name('project-files.show');
        Route::delete('/files/{file}', [ProjectFileController::class, 'destroy'])->name('project-files.destroy');
        Route::get('/files/{file}/download', [ProjectFileController::class, 'download'])->name('project-files.download');
    });
    
    // Маршруты для работы со сметами
    Route::resource('estimates', EstimateController::class);
    
    // Маршруты для управления сотрудниками
    Route::resource('employees', EmployeeController::class)->except(['create', 'edit']);
      // Маршруты для Excel-файлов смет
    Route::get('estimates/{estimate}/export', [EstimateExcelController::class, 'export'])
        ->name('estimates.export');
    Route::get('estimates/{estimate}/export-pdf', [EstimateExcelController::class, 'exportPdf'])
        ->name('estimates.exportPdf');
    
    // Новые маршруты для экспорта смет для заказчика и мастера
    Route::get('estimates/{estimate}/export-client', [EstimateExcelController::class, 'exportClient'])
        ->name('estimates.exportClient');
    Route::get('estimates/{estimate}/export-contractor', [EstimateExcelController::class, 'exportContractor'])
        ->name('estimates.exportContractor');
    Route::get('estimates/{estimate}/export-pdf-client', [EstimateExcelController::class, 'exportPdfClient'])
        ->name('estimates.exportPdfClient');
    Route::get('estimates/{estimate}/export-pdf-contractor', [EstimateExcelController::class, 'exportPdfContractor'])
        ->name('estimates.exportPdfContractor');
        
    Route::get('estimates/{estimate}/data', [EstimateExcelController::class, 'getData'])->name('estimates.getData');
    Route::post('estimates/{estimate}/saveExcel', [EstimateExcelController::class, 'saveExcelData'])->name('estimates.saveExcel');
    Route::post('estimates/{estimate}/upload', [EstimateExcelController::class, 'upload'])->name('estimates.upload');
    
    // Маршруты для управления элементами смет
    Route::post('estimates/{estimate}/items/add', [EstimateItemController::class, 'addRow'])->name('estimates.items.add');
    Route::put('estimates/{estimate}/items/table', [EstimateItemController::class, 'updateTable'])->name('estimates.items.table');
    
    // Маршруты для Excel шаблонов
    Route::get('excel-templates', [ExcelTemplateController::class, 'index'])->name('excel-templates.index');
    Route::get('excel-templates/estimate/{type}', [ExcelTemplateController::class, 'downloadEstimateTemplate'])->name('excel-templates.estimate');
});

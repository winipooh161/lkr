<?php

use App\Http\Controllers\Client\ClientController;
use App\Http\Controllers\Client\ProjectController;
use App\Http\Controllers\Client\ProjectFileController;
use Illuminate\Support\Facades\Route;

// Клиентские маршруты - с middleware для доступа администраторов
Route::prefix('client')->name('client.')->middleware(['auth', 'admin.or.client'])->group(function () {
    // Перенаправляем с корня клиентского раздела на страницу проектов
    Route::get('/', function () {
        return redirect()->route('client.projects.index');
    });
    
    // Маршруты для проектов клиента
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
      // Маршрут для генерации PDF
    Route::post('/projects/generate-pdf', [\App\Http\Controllers\Partner\PdfGeneratorController::class, 'generatePdf'])
        ->name('projects.generate-pdf')
        ->withoutMiddleware(['partner']); // Отключаем middleware partner, так как контроллер находится в Partner namespace
    
    // Маршрут для скачивания файлов
    Route::get('/project-files/{file}/download', [ProjectFileController::class, 'download'])->name('project-files.download');
    
    // Маршрут для скачивания смет
    Route::get('/estimates/{estimate}/download', [\App\Http\Controllers\Client\EstimateController::class, 'download'])->name('estimates.download');
});

<?php

use App\Http\Controllers\Partner\ProjectDocuments\ActFlIpController;
use Illuminate\Support\Facades\Route;

// Маршруты для конкретного типа документа: Акт выполненных работ ФЛ-ИП
Route::middleware(['auth'])->prefix('partner')->name('partner.')->group(function () {
    // Генерация документа "Акт выполненных работ ФЛ-ИП"
    Route::post('/projects/{project}/documents/act-fl-ip/generate', [ActFlIpController::class, 'generateDocument'])
         ->name('projects.documents.act-fl-ip.generate');
});

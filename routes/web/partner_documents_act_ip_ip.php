<?php

use App\Http\Controllers\Partner\ProjectDocuments\ActIpIpController;
use Illuminate\Support\Facades\Route;

// Маршруты для конкретного типа документа: Акт выполненных работ ИП-ИП
Route::middleware(['auth'])->prefix('partner')->name('partner.')->group(function () {
    // Генерация документа "Акт выполненных работ ИП-ИП"
    Route::post('/projects/{project}/documents/act-ip-ip/generate', [ActIpIpController::class, 'generateDocument'])
         ->name('projects.documents.act-ip-ip.generate');
});

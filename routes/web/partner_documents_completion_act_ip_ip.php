<?php

use App\Http\Controllers\Partner\ProjectDocuments\CompletionActIpIpController;
use Illuminate\Support\Facades\Route;

// Маршруты для конкретного типа документа: Акт завершения ИП-ИП
Route::middleware(['auth'])->prefix('partner')->name('partner.')->group(function () {
    // Генерация документа "Акт завершения ремонта ИП-ИП"
    Route::post('/projects/{project}/documents/completion-act-ip-ip/generate', [CompletionActIpIpController::class, 'generateDocument'])
         ->name('projects.documents.completion-act-ip-ip.generate');
});

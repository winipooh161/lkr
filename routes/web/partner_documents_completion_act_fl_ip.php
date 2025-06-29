<?php

use App\Http\Controllers\Partner\ProjectDocuments\CompletionActFlIpController;
use Illuminate\Support\Facades\Route;

// Маршруты для конкретного типа документа: Акт завершения ФЛ-ИП
Route::middleware(['auth'])->prefix('partner')->name('partner.')->group(function () {
    // Генерация документа "Акт завершения ремонта ФЛ-ИП"
    Route::post('/projects/{project}/documents/completion-act-fl-ip/generate', [CompletionActFlIpController::class, 'generateDocument'])
         ->name('projects.documents.completion-act-fl-ip.generate');
});

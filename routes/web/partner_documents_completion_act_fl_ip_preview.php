<?php

use App\Http\Controllers\Partner\ProjectDocuments\CompletionActFlIpController;
use Illuminate\Support\Facades\Route;

// Маршруты для предпросмотра акта завершения ФЛ-ИП
Route::middleware(['auth'])->prefix('partner')->name('partner.')->group(function () {
    // Предпросмотр документа "Акт завершения ремонта ФЛ-ИП"
    Route::post('/projects/{project}/documents/completion-act-fl-ip/preview', [CompletionActFlIpController::class, 'previewDocument'])
         ->name('projects.documents.completion-act-fl-ip.preview');
});

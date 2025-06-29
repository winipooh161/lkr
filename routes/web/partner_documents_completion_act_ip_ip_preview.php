<?php

use App\Http\Controllers\Partner\ProjectDocuments\CompletionActIpIpController;
use Illuminate\Support\Facades\Route;

// Маршруты для предпросмотра акта завершения ИП-ИП
Route::middleware(['auth'])->prefix('partner')->name('partner.')->group(function () {
    // Предпросмотр документа "Акт завершения ремонта ИП-ИП"
    Route::post('/projects/{project}/documents/completion-act-ip-ip/preview', [CompletionActIpIpController::class, 'previewDocument'])
         ->name('projects.documents.completion-act-ip-ip.preview');
});

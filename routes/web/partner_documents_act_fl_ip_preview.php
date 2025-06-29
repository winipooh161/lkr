<?php

use App\Http\Controllers\Partner\ProjectDocuments\ActFlIpController;
use Illuminate\Support\Facades\Route;

// Маршруты для предпросмотра акта выполненных работ ФЛ-ИП
Route::middleware(['auth'])->prefix('partner')->name('partner.')->group(function () {
    // Предпросмотр документа "Акт выполненных работ ФЛ-ИП"
    Route::post('/projects/{project}/documents/act-fl-ip/preview', [ActFlIpController::class, 'previewDocument'])
         ->name('projects.documents.act-fl-ip.preview');
});

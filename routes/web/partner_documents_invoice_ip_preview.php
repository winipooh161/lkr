<?php

use App\Http\Controllers\Partner\ProjectDocuments\InvoiceIpController;
use Illuminate\Support\Facades\Route;

// Маршруты для предпросмотра счета ИП
Route::middleware(['auth'])->prefix('partner')->name('partner.')->group(function () {
    // Предпросмотр документа "Счет ИП"
    Route::post('/projects/{project}/documents/invoice-ip/preview', [InvoiceIpController::class, 'previewDocument'])
         ->name('projects.documents.invoice-ip.preview');
});

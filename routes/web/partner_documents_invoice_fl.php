<?php

use App\Http\Controllers\Partner\ProjectDocuments\InvoiceFlController;
use Illuminate\Support\Facades\Route;

// Маршруты для конкретного типа документа: Счет ФЛ
Route::middleware(['auth'])->prefix('partner')->name('partner.')->group(function () {
    // Генерация документа "Счет ФЛ"
    Route::post('/projects/{project}/documents/invoice-fl/generate', [InvoiceFlController::class, 'generateDocument'])
         ->name('projects.documents.invoice-fl.generate');
});

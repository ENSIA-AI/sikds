<?php

use App\Http\Controllers\Common\DashboardController;
use App\Http\Controllers\Common\DocumentsController;
use App\Http\Controllers\Web\DownloadController;
use App\Http\Controllers\Web\WatermarkTraceabilityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/documents', [DocumentsController::class, 'index'])
            ->name('documents.index');

        Route::get('/documents/upload', [DocumentsController::class, 'create'])
            ->name('documents.create');

        Route::get('/documents/{document}/edit', [DocumentsController::class, 'edit'])
            ->name('documents.edit');

        Route::get('/documents/{document}', [DocumentsController::class, 'show'])
            ->name('documents.show');

        // Document Download
        Route::get('/documents/{id}/download', [DownloadController::class, 'download'])
            ->name('documents.download');

        // Watermark Traceability (index accepts GET query params: q, date_from, date_to, document, user, institution)
        Route::get('/watermark', [WatermarkTraceabilityController::class, 'index'])
            ->name('watermark.index');
        Route::get('/watermark/{uuid}', [WatermarkTraceabilityController::class, 'show'])
            ->name('watermark.show');
    });


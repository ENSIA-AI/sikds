<?php

use App\Http\Controllers\Common\DashboardController;
use App\Http\Controllers\Common\DocumentsController;
use App\Http\Controllers\Web\DownloadController;
use App\Http\Controllers\Web\IndexingController;
use App\Http\Controllers\Web\RagController;
use App\Http\Controllers\Web\WatermarkTraceabilityController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::get('/documents', [DocumentsController::class, 'index'])
            ->name('documents.index');

        // Document Download
        Route::get('/documents/{id}/download', [DownloadController::class, 'download'])
            ->name('documents.download');

        // RAG
        Route::get('/rag', [RagController::class, 'index'])
            ->name('rag.index');
        Route::post('/rag/query', [RagController::class, 'query'])
            ->name('rag.query');

        // Indexing monitor
        Route::get('/indexing', [IndexingController::class, 'index'])
            ->name('indexing.index');
        Route::post('/indexing/{document}/retry', [IndexingController::class, 'retry'])
            ->name('indexing.retry');

        // Watermark Traceability (index accepts GET query params: q, date_from, date_to, document, user, institution)
        Route::get('/watermark', [WatermarkTraceabilityController::class, 'index'])
            ->name('watermark.index');
        Route::get('/watermark/{uuid}', [WatermarkTraceabilityController::class, 'show'])
            ->name('watermark.show');
    });


<?php

use App\Http\Controllers\Common\DashboardController;
use App\Http\Controllers\Common\DocumentsController;
use App\Http\Controllers\Api\DocumentsApiController;
use App\Http\Controllers\Web\DownloadController;
use App\Http\Controllers\Web\IndexingController;
use App\Http\Controllers\Web\RagController;
use App\Http\Controllers\Web\WatermarkTraceabilityController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
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

        // Documents API (secured via auth + permission checks in controller).
        Route::prefix('api/documents')->name('api.documents.')->group(function () {
            Route::get('/', [DocumentsApiController::class, 'index'])->name('index');
            Route::post('/', [DocumentsApiController::class, 'store'])->name('store');
            Route::post('/create', [DocumentsApiController::class, 'store'])->name('create');
            Route::get('/{id}', [DocumentsApiController::class, 'show'])->name('show');
            Route::put('/{id}', [DocumentsApiController::class, 'update'])->name('update');
            Route::patch('/{id}', [DocumentsApiController::class, 'update'])->name('patch');
            Route::post('/{id}/publish', [DocumentsApiController::class, 'publish'])->name('publish');
            Route::post('/{id}/archive', [DocumentsApiController::class, 'archive'])->name('archive');
            Route::delete('/{id}', [DocumentsApiController::class, 'destroy'])->name('destroy');
            Route::post('/{id}/restore', [DocumentsApiController::class, 'restore'])->name('restore');
            Route::get('/{id}/versions', [DocumentsApiController::class, 'versions'])->name('versions');
        });

        // Roles management
        Route::prefix('roles')->name('roles.')->group(function () {
            Route::get('/', [RoleController::class, 'index'])->name('index');
            Route::get('/create', [RoleController::class, 'create'])->name('create');
            Route::post('/', [RoleController::class, 'store'])->name('store');
            Route::get('/{role}', [RoleController::class, 'show'])->name('show');
            Route::get('/{role}/edit', [RoleController::class, 'edit'])->name('edit');
            Route::put('/{role}', [RoleController::class, 'update'])->name('update');
            Route::delete('/{role}', [RoleController::class, 'destroy'])->name('destroy');
        });

        // Institutions
        Route::prefix('institutions')->name('institutions.')->group(function () {
            Route::get('/', [InstitutionController::class, 'index'])->name('index');
            Route::post('/', [InstitutionController::class, 'store'])->name('store');
            Route::match(['put', 'post'], '/{institution}', [InstitutionController::class, 'update'])->name('update');
            Route::delete('/{institution}', [InstitutionController::class, 'destroy'])->name('destroy');
        });

        // Permissions catalog
        Route::prefix('permissions')->name('permissions.')->group(function () {
            Route::get('/', [PermissionController::class, 'index'])->name('index');
        });

        // User management
        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [UserController::class, 'index'])->name('index');
            Route::get('/{user}', [UserController::class, 'show'])->name('show');
            Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
            Route::put('/{user}', [UserController::class, 'update'])->name('update');
            Route::get('/{user}/roles', [UserController::class, 'editRoles'])->name('edit-roles');
            Route::put('/{user}/roles', [UserController::class, 'updateRoles'])->name('update-roles');
            Route::post('/{user}/deactivate', [UserController::class, 'deactivate'])->name('deactivate');
            Route::post('/{user}/activate', [UserController::class, 'activate'])->name('activate');
        });
    });


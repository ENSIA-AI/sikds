<?php

/**
 * Authenticated application routes (loaded from routes/web.php).
 *
 * Permission middleware convention :
 * - `can:permission.name` — Laravel `AuthorizeMiddleware` + Spatie; uses `$user->can(...)`.
 *   This is the default for feature routes below (maps to `App\Domain\Users\Models\Permission`).
 * - `permission:…`, `role:…`, `role_or_permission:…` — Spatie aliases registered in
 *   `bootstrap/app.php`; use when you need Spatie’s middleware specifically.
 * - `App\Providers\AuthServiceProvider` (see `bootstrap/providers.php`) registers
 *   `Gate::before`: **Super Administrateur** passes every `can:…` check; others use Spatie permissions.
 * - Document read vs write: list/index visibility uses OR logic (`document.view.all` |
 *   `document.view.own_institution` | `document.view.assigned`) inside controllers/services.
 *   Mutations (API and forward) additionally require institution scope via
 *   `DocumentApiAuthorizationService::assertInstitutionScope()` unless the user has
 *   `document.view.all`, is the uploader, or shares the uploader’s institution.
 *   Restore: `assertCanRestore()` limits actors to Super Administrateur (SRS §3.1 / §7.2), and
 *   `assertInstitutionScope()` applies like other mutations (`document.view.all`, uploader, or
 *   same institution as uploader).
 *
 * @see \App\Domain\Documents\Services\Api\DocumentApiAuthorizationService
 */

use App\Http\Controllers\Common\DashboardController;
use App\Http\Controllers\Common\DocumentsController;
use App\Http\Controllers\Common\TagsController;
use App\Http\Controllers\Api\DocumentsApiController;
use App\Http\Controllers\Web\DocumentForwardController;
use App\Http\Controllers\Web\DownloadController;
use App\Http\Controllers\Web\IndexingController;
use App\Http\Controllers\Web\AuditController;
use App\Http\Controllers\Web\NotificationInboxController;
use App\Http\Controllers\Web\NotificationsController;
use App\Http\Controllers\Web\SettingsController;
use App\Http\Controllers\Web\RagController;
use App\Http\Controllers\Web\WatermarkTraceabilityController;
use App\Http\Controllers\InstitutionController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])
    ->group(function () {

        // ── Dashboard ─────────────────────────────────────────────────────────
        // Permission: none (any authenticated user)
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        // ── Documents (read/list) ──────────────────────────────────────────────
        // Permission: document.view.all | document.view.own_institution | document.view.assigned
        // (OR-logic enforced in DocumentsController / DocumentApiAuthorizationService)
        Route::get('/documents', [DocumentsController::class, 'index'])
            ->name('documents.index');

        // Static paths must be registered before `/documents/{document}` so `upload` is not
        // captured as a document id (which would hit `show` + document.view.all → 403).

        // Permission: document.create
        Route::get('/documents/upload', [DocumentsController::class, 'create'])
            ->middleware('can:document.create')
            ->name('documents.create');

        // Permission: document.view.all (preview requires full visibility)
        Route::get('/documents/{document}', [DocumentsController::class, 'show'])
            ->middleware('can:document.view.all')
            ->name('documents.show');

        // Permission: document.edit
        Route::get('/documents/{document}/edit', [DocumentsController::class, 'edit'])
            ->middleware('can:document.edit')
            ->name('documents.edit');

        // ── Document Download ──────────────────────────────────────────────────
        // Permission: none beyond auth (download gated by document visibility in controller)
        Route::get('/documents/{id}/download', [DownloadController::class, 'download'])
            ->name('documents.download');

        // ── Document Forward ───────────────────────────────────────────────────
        // Permission: document.forward
        Route::middleware('can:document.forward')->group(function () {
            Route::get('/documents/forward/users/search', [DocumentForwardController::class, 'searchUsers'])
                ->name('documents.forward.search-users');
            Route::post('/documents/{id}/forward', [DocumentForwardController::class, 'store'])
                ->name('documents.forward.store');
        });

        // ── Tags ───────────────────────────────────────────────────────────────
        // Permission: tag.manage (mutations only; index is visible to all authenticated users)
        Route::get('/tags', [TagsController::class, 'index'])->name('tags.index');
        Route::middleware('can:tag.manage')->group(function () {
            Route::post('/tags', [TagsController::class, 'store'])->name('tags.store');
            Route::patch('/tags/{tag}', [TagsController::class, 'update'])->name('tags.update');
            Route::delete('/tags/{tag}', [TagsController::class, 'destroy'])->name('tags.destroy');
        });

        // ── RAG ────────────────────────────────────────────────────────────────
        // Permission: rag.query
        Route::middleware('can:rag.query')->group(function () {
            Route::get('/rag', [RagController::class, 'index'])->name('rag.index');
            Route::post('/rag/query', [RagController::class, 'query'])->name('rag.query');
        });

        // ── Indexing monitor ───────────────────────────────────────────────────
        // Permission: indexing.manage (Super Administrateur also passes via AuthServiceProvider::Gate::before).
        Route::get('/indexing', [IndexingController::class, 'index'])->name('indexing.index');
        Route::post('/indexing/{document}/retry', [IndexingController::class, 'retry'])->name('indexing.retry');

        // ── Audit log ──────────────────────────────────────────────────────────
        // Permission: audit.view
        Route::middleware('can:audit.view')->group(function () {
            Route::get('/audits', [AuditController::class, 'index'])->name('audits.index');
            Route::get('/audits/export', [AuditController::class, 'export'])->name('audits.export');
        });

        // ── Admin notifications feed ───────────────────────────────────────────
        // Permission: audit.view (system-wide notification list is admin-only)
        Route::get('/notifications', [NotificationsController::class, 'index'])
            ->middleware('can:audit.view')
            ->name('notifications.index');

        // ── Per-user notification inbox (no extra permission needed) ───────────
        Route::get('/notifications/inbox', [NotificationInboxController::class, 'index'])
            ->name('notifications.inbox');
        Route::get('/notifications/latest', [NotificationInboxController::class, 'latest'])
            ->name('notifications.latest');
        Route::post('/notifications/{notification}/read', [NotificationInboxController::class, 'read'])
            ->name('notifications.read');
        Route::post('/notifications/read-all', [NotificationInboxController::class, 'markAllRead'])
            ->name('notifications.read-all');

        // ── Settings ───────────────────────────────────────────────────────────
        // Permission: audit.view (settings are admin-only)
        Route::middleware('can:audit.view')->group(function () {
            Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
            Route::post('/settings/update', [SettingsController::class, 'update'])->name('settings.update');
        });

        // ── Watermark traceability ─────────────────────────────────────────────
        // Permission: audit.view
        Route::middleware('can:audit.view')->group(function () {
            Route::get('/watermark', [WatermarkTraceabilityController::class, 'index'])->name('watermark.index');
            Route::get('/watermark/{uuid}', [WatermarkTraceabilityController::class, 'show'])->name('watermark.show');
        });

        // ── Documents API ──────────────────────────────────────────────────────
        // Read: `DocumentApiQueryService` asserts list/preview permissions internally.
        // Write: each route adds `can:document.*` middleware; commands call
        // `DocumentApiAuthorizationService::assertInstitutionScope()` where applicable.
        Route::prefix('api/documents')->name('api.documents.')->group(function () {
            // Read endpoints – OR-logic (view.all | own_institution | assigned) enforced in service
            Route::get('/', [DocumentsApiController::class, 'index'])->name('index');
            Route::get('/{id}', [DocumentsApiController::class, 'show'])->name('show');
            Route::get('/{id}/versions', [DocumentsApiController::class, 'versions'])->name('versions');

            // Write endpoints – individual permission guards applied at route level
            Route::post('/', [DocumentsApiController::class, 'store'])
                ->middleware('can:document.create')
                ->name('store');
            Route::post('/create', [DocumentsApiController::class, 'store'])
                ->middleware('can:document.create')
                ->name('create');
            Route::put('/{id}', [DocumentsApiController::class, 'update'])
                ->middleware('can:document.edit')
                ->name('update');
            Route::patch('/{id}', [DocumentsApiController::class, 'update'])
                ->middleware('can:document.edit')
                ->name('patch');
            Route::post('/{id}/publish', [DocumentsApiController::class, 'publish'])
                ->middleware('can:document.publish')
                ->name('publish');
            Route::post('/{id}/archive', [DocumentsApiController::class, 'archive'])
                ->middleware('can:document.publish')
                ->name('archive');
            Route::delete('/{id}', [DocumentsApiController::class, 'destroy'])
                ->middleware('can:document.delete')
                ->name('destroy');
            // SRS §3.1 / §7.2: assertCanRestore() is Super Administrateur–only; can:document.restore is route middleware
            // (super admin passes via Gate::before / seeded permissions; peers with only document.restore are blocked in service).
            // Restore also uses assertInstitutionScope() like update/delete unless view.all / uploader / same institution.
            Route::post('/{id}/restore', [DocumentsApiController::class, 'restore'])
                ->middleware('can:document.restore')
                ->name('restore');
        });

        // ── Roles management ───────────────────────────────────────────────────
        Route::prefix('roles')->name('roles.')->group(function () {
            // Permission: role.view
            Route::get('/', [RoleController::class, 'index'])->middleware('can:role.view')->name('index');
            Route::get('/{role}', [RoleController::class, 'show'])->middleware('can:role.view')->name('show');

            // Permission: role.create
            Route::get('/create', [RoleController::class, 'create'])->middleware('can:role.create')->name('create');
            Route::post('/', [RoleController::class, 'store'])->middleware('can:role.create')->name('store');

            // Permission: role.edit
            Route::get('/{role}/edit', [RoleController::class, 'edit'])->middleware('can:role.edit')->name('edit');
            Route::put('/{role}', [RoleController::class, 'update'])->middleware('can:role.edit')->name('update');

            // Permission: role.delete
            Route::delete('/{role}', [RoleController::class, 'destroy'])->middleware('can:role.delete')->name('destroy');
        });

        // ── Institutions ───────────────────────────────────────────────────────
        Route::prefix('institutions')->name('institutions.')->group(function () {
            // Permission: institution.view
            Route::get('/', [InstitutionController::class, 'index'])->middleware('can:institution.view')->name('index');

            // Permission: institution.manage (create/update/delete checked in controller)
            Route::post('/', [InstitutionController::class, 'store'])->name('store');
            Route::match(['put', 'post'], '/{institution}', [InstitutionController::class, 'update'])->name('update');
            Route::delete('/{institution}', [InstitutionController::class, 'destroy'])->middleware('can:institution.delete')->name('destroy');
        });

        // ── Permissions catalog ────────────────────────────────────────────────
        // Permission: user.view.all (admin-level users who can manage permissions)
        Route::prefix('permissions')->name('permissions.')->group(function () {
            Route::get('/', [PermissionController::class, 'index'])->middleware('can:user.view.all')->name('index');
        });

        // ── User management ────────────────────────────────────────────────────
        Route::prefix('users')->name('users.')->group(function () {
            // Permission: user.view.all
            Route::get('/', [UserController::class, 'index'])->middleware('can:user.view.all')->name('index');
            Route::get('/{user}', [UserController::class, 'show'])->middleware('can:user.view.all')->name('show');

            // Permission: user.manage
            Route::get('/create', [UserController::class, 'create'])->middleware('can:user.manage')->name('create');
            Route::post('/', [UserController::class, 'store'])->middleware('can:user.manage')->name('store');
            Route::get('/{user}/edit', [UserController::class, 'edit'])->middleware('can:user.manage')->name('edit');
            Route::put('/{user}', [UserController::class, 'update'])->middleware('can:user.manage')->name('update');

            // Permission: user.assign.permissions
            Route::get('/{user}/permissions', [UserController::class, 'editPermissions'])
                ->middleware('can:user.assign.permissions')
                ->name('edit-permissions');
            Route::put('/{user}/permissions', [UserController::class, 'updatePermissions'])
                ->middleware('can:user.assign.permissions')
                ->name('update-permissions');

            // Permission: user.deactivate
            Route::post('/{user}/deactivate', [UserController::class, 'deactivate'])
                ->middleware('can:user.deactivate')
                ->name('deactivate');
            Route::post('/{user}/activate', [UserController::class, 'activate'])
                ->middleware('can:user.deactivate')
                ->name('activate');
        });
    });


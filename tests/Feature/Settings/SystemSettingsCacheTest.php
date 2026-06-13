<?php

declare(strict_types=1);

use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\User;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('always exposes every default section even when the cache predates it', function (): void {
    // Reproduces the production bug: a `rememberForever` cache populated before the
    // `rag` section existed must NOT cause `$managed['rag']` to be missing.
    Cache::forever(SystemSettingsService::CACHE_KEY, ['audit.retention_days' => 999]);

    $all = app(SystemSettingsService::class)->all();

    expect($all)->toHaveKey('rag')
        ->and($all['rag']['llm_model'])->not->toBeEmpty()
        ->and($all['rag'])->toHaveKeys(['min_confidence', 'candidate_pool', 'top_n', 'reranking_enabled', 'hybrid_enabled'])
        // a stale persisted override is still honored
        ->and($all['audit']['retention_days'])->toBe(999);
});

it('renders the settings page even when the settings cache is stale', function (): void {
    Cache::forever(SystemSettingsService::CACHE_KEY, ['audit.retention_days' => 999]);

    $user = User::factory()->create();
    Permission::query()->firstOrCreate(
        ['name' => 'settings.view', 'guard_name' => 'web'],
        ['code' => 'settings.view', 'description' => 'settings.view', 'category' => 'settings']
    );
    $user->givePermissionTo('settings.view');

    $this->actingAs($user)
        ->get('/settings')
        ->assertOk()
        ->assertSee('llama-3.3-70b-versatile'); // RAG model input rendered, no 500
});

<?php

declare(strict_types=1);

use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\User;
use App\Services\Settings\SystemSettingsService;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function settingsGrant(User $user, string $code): void
{
    Permission::query()->firstOrCreate(
        ['name' => $code, 'guard_name' => 'web'],
        ['code' => $code, 'description' => $code, 'category' => 'settings']
    );
    $user->givePermissionTo($code);
}

it('denies the settings page to a user with only audit.view', function (): void {
    $user = User::factory()->create();
    settingsGrant($user, 'audit.view');

    $this->actingAs($user)->get('/settings')->assertForbidden();
});

it('allows the settings page for a user with settings.view', function (): void {
    $user = User::factory()->create();
    settingsGrant($user, 'settings.view');

    $this->actingAs($user)->get('/settings')->assertOk();
});

it('forbids updating settings without settings.manage even with settings.view', function (): void {
    $user = User::factory()->create();
    settingsGrant($user, 'settings.view');

    $this->actingAs($user)
        ->post('/settings/update', [
            'section' => 'audit',
            'retention_days' => 365,
            'export_max_days' => 30,
        ])
        ->assertForbidden();
});

it('allows updating settings with settings.manage', function (): void {
    $user = User::factory()->create();
    settingsGrant($user, 'settings.manage');

    $this->actingAs($user)
        ->post('/settings/update', [
            'section' => 'audit',
            'retention_days' => 365,
            'export_max_days' => 30,
        ])
        ->assertRedirect(route('settings.index'));
});

it('persists RAG knobs that the query service then reads', function (): void {
    $user = User::factory()->create();
    settingsGrant($user, 'settings.manage');

    $this->actingAs($user)
        ->post('/settings/update', [
            'section' => 'rag',
            'llm_model' => 'custom-model-v2',
            'min_confidence' => 0.42,
            'candidate_pool' => 33,
            'top_n' => 7,
            'reranking_enabled' => '1',
            // hybrid_enabled omitted → should persist as false
        ])
        ->assertRedirect(route('settings.index'));

    $settings = app(SystemSettingsService::class);
    expect($settings->get('rag.llm_model'))->toBe('custom-model-v2')
        ->and((float) $settings->get('rag.min_confidence'))->toBe(0.42)
        ->and((int) $settings->get('rag.candidate_pool'))->toBe(33)
        ->and((int) $settings->get('rag.top_n'))->toBe(7)
        ->and((bool) $settings->get('rag.reranking_enabled'))->toBeTrue()
        ->and((bool) $settings->get('rag.hybrid_enabled'))->toBeFalse();
});

it('rejects out-of-range RAG knobs', function (): void {
    $user = User::factory()->create();
    settingsGrant($user, 'settings.manage');

    $this->actingAs($user)
        ->from(route('settings.index'))
        ->post('/settings/update', [
            'section' => 'rag',
            'llm_model' => 'm',
            'min_confidence' => 5,        // > 1
            'candidate_pool' => 9999,     // > 200
            'top_n' => 0,                 // < 1
        ])
        ->assertSessionHasErrors(['min_confidence', 'candidate_pool', 'top_n']);
});

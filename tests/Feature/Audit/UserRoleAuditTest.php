<?php

declare(strict_types=1);

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Audit\Services\AuditService;
use App\Domain\Users\Actions\AssignRolesToUserAction;
use App\Domain\Users\Actions\CreateRoleAction;
use App\Domain\Users\Actions\DeleteRoleAction;
use App\Domain\Users\Actions\SyncUserPermissionsAction;
use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use App\Domain\Users\Models\User;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

it('writes a role.created audit log when CreateRoleAction runs', function (): void {
    $actor = User::factory()->create();

    $perm = Permission::query()->create([
        'code' => 'document.view.public',
        'name' => 'Voir les documents publics',
        'category' => 'documents',
        'guard_name' => 'web',
    ]);

    $action = app(CreateRoleAction::class);
    $role = $action->execute(
        ['name' => 'Auditeur Test', 'permission_ids' => [$perm->id]],
        $actor->id,
    );

    $log = AuditLog::query()
        ->where('event_type', 'role.created')
        ->where('resource_id', $role->id)
        ->firstOrFail();

    expect($log->resource_type)->toBe('role')
        ->and($log->result)->toBe('success')
        ->and($log->metadata['event_code'] ?? null)->toBe('role.created')
        ->and($log->metadata['actor_context']['id'] ?? null)->toBe($actor->id)
        ->and($log->metadata['target_context']['role_id'] ?? null)->toBe($role->id)
        ->and($log->metadata['role_name'] ?? null)->toBe('Auditeur Test')
        ->and($log->metadata['permission_codes'] ?? [])->toContain('document.view.public');
});

it('refuses to delete a role assigned to users and writes a failed audit log', function (): void {
    $role = Role::factory()->create(['name' => 'Rôle Occupé']);
    $assigned = User::factory()->create();
    $assigned->roles()->attach($role->id);

    $action = app(DeleteRoleAction::class);

    expect(fn () => $action->execute($role))
        ->toThrow(\Exception::class);

    expect(Role::query()->find($role->id))->not->toBeNull();

    $log = AuditLog::query()
        ->where('event_type', 'role.deleted')
        ->where('resource_id', $role->id)
        ->latest('created_at')
        ->firstOrFail();

    expect($log->result)->toBe('failed')
        ->and($log->metadata['reason'] ?? null)->toBe('role_in_use')
        ->and($log->metadata['failure_reason']['code'] ?? null)->toBe('role_in_use')
        ->and($log->metadata['failure_reason']['message'] ?? null)->not->toBeEmpty();
});

it('refuses to delete a system role and writes a failed audit log', function (): void {
    $role = Role::factory()->system()->create(['name' => 'Système Test']);

    $action = app(DeleteRoleAction::class);

    expect(fn () => $action->execute($role))
        ->toThrow(\Exception::class);

    $log = AuditLog::query()
        ->where('event_type', 'role.deleted')
        ->where('resource_id', $role->id)
        ->latest('created_at')
        ->firstOrFail();

    expect($log->result)->toBe('failed')
        ->and($log->metadata['reason'] ?? null)->toBe('system_role')
        ->and($log->metadata['failure_reason']['code'] ?? null)->toBe('system_role');
});

it('emits role.assigned and role.removed when AssignRolesToUserAction changes the set', function (): void {
    $actor = User::factory()->create();
    $target = User::factory()->create();

    $oldRole = Role::factory()->create(['name' => 'Ancien']);
    $newRole = Role::factory()->create(['name' => 'Nouveau']);

    $target->roles()->attach($oldRole->id);

    $action = app(AssignRolesToUserAction::class);
    $action->execute($target, [$newRole->id], $actor->id);

    $assigned = AuditLog::query()
        ->where('event_type', 'role.assigned')
        ->where('resource_id', $target->id)
        ->where('metadata->role_id', $newRole->id)
        ->firstOrFail();

    $removed = AuditLog::query()
        ->where('event_type', 'role.removed')
        ->where('resource_id', $target->id)
        ->where('metadata->role_id', $oldRole->id)
        ->firstOrFail();

    expect($assigned->metadata['role_name'] ?? null)->toBe('Nouveau')
        ->and($assigned->metadata['actor_context']['id'] ?? null)->toBe($actor->id)
        ->and($assigned->metadata['target_context']['user_id'] ?? null)->toBe($target->id)
        ->and($removed->metadata['role_name'] ?? null)->toBe('Ancien');
});

it('emits permission.assigned, permission.removed and permissions.changed via SyncUserPermissionsAction', function (): void {
    $actor = User::factory()->create();
    $target = User::factory()->create();

    $role = Role::factory()->create(['name' => 'Rôle de base']);

    $existingPerm = Permission::query()->create([
        'code' => 'audit.view',
        'name' => 'Voir audits',
        'category' => 'audit',
        'guard_name' => 'web',
    ]);
    $newPerm = Permission::query()->create([
        'code' => 'document.create',
        'name' => 'Créer document',
        'category' => 'documents',
        'guard_name' => 'web',
    ]);

    $target->roles()->attach($role->id);
    $target->givePermissionTo($existingPerm);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $action = app(SyncUserPermissionsAction::class);
    $action->execute(
        $target,
        ['role_ids' => [$role->id], 'permission_ids' => [$newPerm->id]],
        $actor->id,
    );

    $added = AuditLog::query()
        ->where('event_type', 'permission.assigned')
        ->where('resource_id', $target->id)
        ->where('metadata->permission_id', $newPerm->id)
        ->firstOrFail();

    $removed = AuditLog::query()
        ->where('event_type', 'permission.removed')
        ->where('resource_id', $target->id)
        ->where('metadata->permission_id', $existingPerm->id)
        ->firstOrFail();

    $summary = AuditLog::query()
        ->where('event_type', 'permissions.changed')
        ->where('resource_id', $target->id)
        ->latest('created_at')
        ->firstOrFail();

    expect($added->metadata['permission_code'] ?? null)->toBe('document.create')
        ->and($removed->metadata['permission_code'] ?? null)->toBe('audit.view')
        ->and($summary->metadata['added_permission_ids'] ?? [])->toContain($newPerm->id)
        ->and($summary->metadata['removed_permission_ids'] ?? [])->toContain($existingPerm->id)
        ->and($summary->metadata['changes']['permission_ids']['before'] ?? null)->toContain($existingPerm->id)
        ->and($summary->metadata['changes']['permission_ids']['after'] ?? null)->toContain($newPerm->id);
});

it('AuditService.record fills user metadata from the authenticated user when none is passed', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    app(AuditService::class)->record(
        eventType: 'demo.event',
        resourceType: 'user',
        resourceId: $user->id,
        metadata: ['source' => 'unit'],
    );

    $log = AuditLog::query()
        ->where('event_type', 'demo.event')
        ->where('resource_id', $user->id)
        ->firstOrFail();

    expect($log->user_id)->toBe($user->id)
        ->and($log->user_email)->toBe($user->email)
        ->and($log->metadata['source'] ?? null)->toBe('unit')
        ->and($log->metadata['event_code'] ?? null)->toBe('demo.event')
        ->and($log->metadata['actor_context']['email'] ?? null)->toBe($user->email)
        ->and($log->metadata['target_context']['user_id'] ?? null)->toBe($user->id);
});

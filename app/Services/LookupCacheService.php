<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\Users\Models\Institution;
use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class LookupCacheService
{
    private const TTL = 3600;

    public const KEY_INSTITUTIONS_ACTIVE = 'lookups.institutions.active';
    public const KEY_INSTITUTIONS_ACTIVE_BY_TYPE = 'lookups.institutions.active.by_type';
    public const KEY_ROLES = 'lookups.roles';
    public const KEY_ROLES_WITH_PERMISSIONS = 'lookups.roles.with_permissions';
    public const KEY_ROLES_WITH_PERMISSION_COUNT = 'lookups.roles.with_permission_count';
    public const KEY_ROLES_WITH_PERMISSION_DETAILS = 'lookups.roles.with_permission_details';
    public const KEY_PERMISSIONS_GROUPED = 'lookups.permissions.grouped_by_category';

    public function activeInstitutions(): Collection
    {
        return Cache::remember(self::KEY_INSTITUTIONS_ACTIVE, self::TTL, fn () => Institution::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get());
    }

    public function activeInstitutionsByType(): Collection
    {
        return Cache::remember(self::KEY_INSTITUTIONS_ACTIVE_BY_TYPE, self::TTL, fn () => Institution::query()
            ->where('is_active', true)
            ->orderBy('type', 'desc')
            ->orderBy('name')
            ->get());
    }

    public function roles(): Collection
    {
        return Cache::remember(self::KEY_ROLES, self::TTL, fn () => Role::query()
            ->orderBy('is_system_role', 'desc')
            ->orderBy('name')
            ->get());
    }

    public function rolesWithPermissions(): Collection
    {
        return Cache::remember(self::KEY_ROLES_WITH_PERMISSIONS, self::TTL, fn () => Role::query()
            ->with(['permissions' => fn ($q) => $q->orderBy('category')->orderBy('name')])
            ->orderBy('is_system_role', 'desc')
            ->orderBy('name')
            ->get());
    }

    public function rolesWithPermissionCount(): Collection
    {
        return Cache::remember(self::KEY_ROLES_WITH_PERMISSION_COUNT, self::TTL, fn () => Role::query()
            ->withCount('permissions')
            ->orderBy('is_system_role', 'desc')
            ->orderBy('name')
            ->get());
    }

    public function rolesWithPermissionDetails(): Collection
    {
        return Cache::remember(self::KEY_ROLES_WITH_PERMISSION_DETAILS, self::TTL, fn () => Role::query()
            ->withCount('permissions')
            ->with('permissions:id,name,code,category')
            ->orderBy('is_system_role', 'desc')
            ->orderBy('name')
            ->get());
    }

    public function permissionsGroupedByCategory(): Collection
    {
        return Cache::remember(self::KEY_PERMISSIONS_GROUPED, self::TTL, fn () => Permission::query()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category'));
    }

    public static function flushInstitutions(): void
    {
        Cache::forget(self::KEY_INSTITUTIONS_ACTIVE);
        Cache::forget(self::KEY_INSTITUTIONS_ACTIVE_BY_TYPE);
    }

    public static function flushRoles(): void
    {
        Cache::forget(self::KEY_ROLES);
        Cache::forget(self::KEY_ROLES_WITH_PERMISSIONS);
        Cache::forget(self::KEY_ROLES_WITH_PERMISSION_COUNT);
        Cache::forget(self::KEY_ROLES_WITH_PERMISSION_DETAILS);
    }

    public static function flushPermissions(): void
    {
        Cache::forget(self::KEY_PERMISSIONS_GROUPED);
        self::flushRoles();
    }
}

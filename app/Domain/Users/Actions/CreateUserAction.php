<?php

declare(strict_types=1);

namespace App\Domain\Users\Actions;

use App\Domain\Audit\Services\AuditService;
use App\Domain\Users\Models\Institution;
use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

final class CreateUserAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    /**
     * Create a new user account with roles and optional custom permissions.
     */
    public function execute(array $data, int $createdById): User
    {
        // Validate institution exists and is active
        $institution = Institution::where('id', $data['institution_id'])
            ->where('is_active', true)
            ->firstOr(function () {
                throw new \Exception('L\'institution sélectionnée n\'existe pas ou est inactive.');
            });

        // Validate at least one role
        if (empty($data['role_ids'])) {
            throw new \Exception('L\'utilisateur doit avoir au moins un rôle.');
        }

        $roles = Role::whereIn('id', $data['role_ids'])->get();
        if ($roles->count() !== count($data['role_ids'])) {
            throw new \Exception('Un ou plusieurs rôles sélectionnés sont invalides.');
        }

        // Validate custom permissions if provided
        $customPermissions = collect();
        if (! empty($data['permission_ids'])) {
            $customPermissions = Permission::whereIn('id', $data['permission_ids'])->get();
            if ($customPermissions->count() !== count($data['permission_ids'])) {
                throw new \Exception('Une ou plusieurs permissions sélectionnées sont invalides.');
            }
        }

        // Permission dependencies (auto-required)
        // Example: document.create implies tag.assign.
        $rolePermissionCodes = Role::query()
            ->whereIn('id', $data['role_ids'])
            ->with('permissions:id,code')
            ->get()
            ->pluck('permissions')
            ->flatten()
            ->pluck('code')
            ->filter()
            ->map(fn ($c) => (string) $c)
            ->all();
        $effectiveCodes = array_values(array_unique([
            ...$rolePermissionCodes,
            ...$customPermissions->pluck('code')->filter()->map(fn ($c) => (string) $c)->all(),
        ]));
        $requiredCodes = [];
        if (in_array('document.create', $effectiveCodes, true)) {
            $requiredCodes[] = 'tag.assign';
        }
        $missingCodes = array_values(array_diff(array_values(array_unique($requiredCodes)), $effectiveCodes));
        if ($missingCodes !== []) {
            $extra = Permission::query()->whereIn('code', $missingCodes)->get();
            $customPermissions = $customPermissions->concat($extra)->unique('id')->values();
        }

        $user = DB::transaction(function () use ($data, $createdById, $customPermissions) {
            // Generate username from email
            $username = explode('@', $data['email'])[0];

            // Create user
            $user = User::create([
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'username' => $username,
                'institution_id' => $data['institution_id'],
                'auth_type' => $data['auth_type'] ?? 'sso',
                'auth_domain' => $this->extractDomain($data['email']),
                'password' => $data['auth_type'] === 'local' && isset($data['password'])
                    ? Hash::make($data['password'])
                    : null,
                'is_active' => $data['is_active'] ?? true,
                'created_by' => $createdById,
            ]);

            // Assign roles
            $syncData = [];
            foreach ($data['role_ids'] as $roleId) {
                $syncData[$roleId] = [
                    'assigned_at' => now(),
                    'assigned_by' => $createdById,
                ];
            }
            $user->roles()->sync($syncData);

            // Assign custom permissions (direct, not via roles)
            if ($customPermissions->isNotEmpty()) {
                $user->syncPermissions($customPermissions);
            }

            return $user->fresh('institution', 'roles.permissions');
        });

        $this->audit->record(
            eventType: 'user.created',
            resourceType: 'user',
            resourceId: $user->id,
            metadata: [
                'target_user_id'   => $user->id,
                'target_user_name' => $user->full_name,
                'target_email'     => $user->email,
                'institution_id'   => $user->institution_id,
                'auth_type'        => $user->auth_type,
                'role_ids'         => array_values(array_map('intval', $data['role_ids'])),
                'role_names'       => $roles->pluck('name')->all(),
                'permission_ids'   => array_values(array_map('intval', $data['permission_ids'] ?? [])),
                'is_active'        => (bool) $user->is_active,
            ],
        );

        return $user;
    }

    private function extractDomain(string $email): string
    {
        return substr(strrchr($email, "@"), 1);
    }
}

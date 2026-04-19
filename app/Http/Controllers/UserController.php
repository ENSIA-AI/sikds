<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Users\Actions\ActivateUserAction;
use App\Domain\Users\Actions\CreateUserAction;
use App\Domain\Users\Actions\DeactivateUserAction;
use App\Domain\Users\Actions\SyncUserPermissionsAction;
use App\Domain\Users\Actions\UpdateUserAction;
use App\Domain\Users\Models\Institution;
use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use App\Domain\Users\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\SyncUserPermissionsRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class UserController extends Controller
{
    public function __construct(
        private readonly CreateUserAction $createUserAction,
        private readonly UpdateUserAction $updateUserAction,
        private readonly SyncUserPermissionsAction $syncUserPermissionsAction,
        private readonly DeactivateUserAction $deactivateUserAction,
        private readonly ActivateUserAction $activateUserAction,
    ) {
    }

    /**
     * Display a listing of users.
     */
    public function index(Request $request): View
    {
        $this->authorize('user.view.all');

        $usersQuery = User::query()
            ->with(['institution:id,name,code', 'roles:id,name,slug'])
            ->when($request->institution_id, function ($query, $institutionId) {
                $query->where('institution_id', $institutionId);
            })
            ->when($request->is_active !== null, function ($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->when($request->role_id, function ($query, $roleId) {
                $query->whereHas('roles', function ($q) use ($roleId) {
                    $q->where('roles.id', $roleId);
                });
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'ilike', "%{$search}%")
                      ->orWhere('email', 'ilike', "%{$search}%")
                      ->orWhereHas('institution', function ($iq) use ($search) {
                          $iq->where('name', 'ilike', "%{$search}%")
                             ->orWhere('code', 'ilike', "%{$search}%");
                      });
                });
            });

        $nameDirection = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        if ($request->query('sort') === 'name') {
            $usersQuery->orderBy('full_name', $nameDirection);
        } else {
            $usersQuery->orderBy('full_name', 'asc');
        }

        $users = $usersQuery
            ->paginate(20)
            ->withQueryString();

        $institutions = Institution::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $roles = Role::query()
            ->orderBy('is_system_role', 'desc')
            ->orderBy('name')
            ->get();

        $rolesForUi = Role::query()
            ->with(['permissions' => fn ($q) => $q->orderBy('category')->orderBy('name')])
            ->orderBy('is_system_role', 'desc')
            ->orderBy('name')
            ->get();

        $permissionsGrouped = Permission::query()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        $stats = [
            'total_users' => User::query()->count(),
            'active_users' => User::query()->where('is_active', true)->count(),
            'inactive_users' => User::query()->where('is_active', false)->count(),
        ];

        $userRowPayloads = collect($users->items())->mapWithKeys(
            fn (User $u): array => [$u->id => $this->userPayloadForTable($u)]
        )->all();

        return view('users', [
            'users' => $users,
            'institutions' => $institutions,
            'roles' => $roles,
            'rolesForUi' => $rolesForUi,
            'permissionsByCategory' => $permissionsGrouped,
            'stats' => $stats,
            'userRowPayloads' => $userRowPayloads,
            'filters' => [
                'institution_id' => $request->institution_id,
                'role_id' => $request->role_id,
                'is_active' => $request->is_active,
                'search' => $request->search,
                'sort' => $request->query('sort'),
                'direction' => $request->query('direction'),
            ],
        ]);
    }

    /**
     * Show the form for creating a new user.
     */
    public function create(): View
    {
        $this->authorize('user.manage');
        $this->authorize('user.assign.permissions');

        $institutions = Institution::query()
            ->where('is_active', true)
            ->orderBy('type', 'desc')
            ->orderBy('name')
            ->get();

        $roles = Role::query()
            ->withCount('permissions')
            ->orderBy('is_system_role', 'desc')
            ->orderBy('name')
            ->get();

        $permissions = Permission::query()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        return view('users.create', [
            'institutions' => $institutions,
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created user.
     */
    public function store(StoreUserRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $user = $this->createUserAction->execute(
                $request->validated(),
                $request->user()->id
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => "L'utilisateur « {$user->full_name} » a été créé avec succès.",
                    'user' => $this->userPayloadForTable($user->fresh(['institution', 'roles'])),
                ], 201);
            }

            return redirect()
                ->route('users.show', $user)
                ->with('success', "L'utilisateur « {$user->full_name} » a été créé avec succès.");

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()
                ->withInput()
                ->with('error', "Erreur : {$e->getMessage()}");
        }
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): View
    {
        $this->authorize('user.view.all');

        $user->load([
            'institution',
            'roles.permissions' => fn($q) => $q->orderBy('category')->orderBy('name'),
            'creator',
        ]);

        // Get permissions from roles
        $rolePermissions = $user->getRoleBasedPermissions()->groupBy('category');
        
        // Get custom (direct) permissions
        $customPermissions = $user->getCustomPermissions()->groupBy('category');
        
        // Get all effective permissions
        $allPermissions = $user->getEffectivePermissions()->groupBy('category');

        return view('users.show', [
            'user' => $user,
            'rolePermissions' => $rolePermissions,
            'customPermissions' => $customPermissions,
            'allPermissions' => $allPermissions,
        ]);
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user): View
    {
        $this->authorize('user.manage');

        $institutions = Institution::query()
            ->where('is_active', true)
            ->orderBy('type', 'desc')
            ->orderBy('name')
            ->get();

        return view('users.edit', [
            'user' => $user,
            'institutions' => $institutions,
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        try {
            $user = $this->updateUserAction->execute($user, $request->validated());

            return redirect()
                ->route('users.show', $user)
                ->with('success', "L'utilisateur « {$user->full_name} » a été mis à jour.");

        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', "Erreur : {$e->getMessage()}");
        }
    }

    /**
     * Show the form for managing user's roles and custom permissions.
     */
    public function editPermissions(User $user): View
    {
        // Require BOTH permissions
        $this->authorize('user.manage');
        $this->authorize('user.assign.permissions');

        $roles = Role::query()
            ->withCount('permissions')
            ->with('permissions:id,name,code,category')
            ->orderBy('is_system_role', 'desc')
            ->orderBy('name')
            ->get();

        $allPermissions = Permission::query()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        $user->load('roles');

        // Get current role-based permissions
        $roleBasedPermissionIds = $user->getRoleBasedPermissions()->pluck('id')->toArray();
        
        // Get current custom permissions
        $customPermissionIds = $user->getCustomPermissions()->pluck('id')->toArray();

        return view('users.edit-permissions', [
            'user' => $user,
            'roles' => $roles,
            'allPermissions' => $allPermissions,
            'roleBasedPermissionIds' => $roleBasedPermissionIds,
            'customPermissionIds' => $customPermissionIds,
        ]);
    }

    /**
     * Update user's roles and custom permissions.
     */
    public function updatePermissions(SyncUserPermissionsRequest $request, User $user): RedirectResponse|JsonResponse
    {
        try {
            $updated = $this->syncUserPermissionsAction->execute(
                $user,
                $request->validated(),
                $request->user()->id
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => "Les rôles et permissions de « {$updated->full_name} » ont été mis à jour.",
                    'user' => $this->userPayloadForTable($updated->fresh(['institution', 'roles'])),
                ]);
            }

            return redirect()
                ->route('users.show', $user)
                ->with('success', "Les rôles et permissions de « {$user->full_name} » ont été mis à jour.");

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()
                ->withInput()
                ->with('error', "Erreur : {$e->getMessage()}");
        }
    }

    /**
     * Deactivate a user.
     */
    public function deactivate(User $user): RedirectResponse
    {
        $this->authorize('user.deactivate');

        try {
            $this->deactivateUserAction->execute($user, auth()->id());

            return back()->with('success', "L'utilisateur « {$user->full_name} » a été désactivé.");

        } catch (\Exception $e) {
            return back()->with('error', "Erreur : {$e->getMessage()}");
        }
    }

    /**
     * Reactivate a user.
     */
    public function activate(User $user): RedirectResponse
    {
        $this->authorize('user.manage');

        $this->activateUserAction->execute($user);

        return back()->with('success', "L'utilisateur « {$user->full_name} » a été réactivé.");
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayloadForTable(User $user): array
    {
        $user->loadMissing(['institution:id,name,code', 'roles:id,name,slug']);

        return [
            'id' => $user->id,
            'full_name' => $user->full_name,
            'email' => $user->email,
            'is_active' => (bool) $user->is_active,
            'last_login_at' => $user->last_login_at?->toIso8601String(),
            'created_at' => $user->created_at?->toIso8601String(),
            'updated_at' => $user->updated_at?->toIso8601String(),
            'institution' => $user->institution ? [
                'id' => $user->institution->id,
                'name' => $user->institution->name,
                'code' => $user->institution->code,
            ] : null,
            'roles' => $user->roles->map(fn (Role $r) => [
                'id' => $r->id,
                'name' => $r->name,
                'slug' => $r->slug,
            ])->values()->all(),
            'custom_permission_ids' => $user->getDirectPermissions()->pluck('id')->values()->all(),
        ];
    }
}
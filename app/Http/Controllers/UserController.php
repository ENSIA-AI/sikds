<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Users\Actions\ActivateUserAction;
use App\Domain\Users\Actions\CreateUserAction;
use App\Domain\Users\Actions\DeactivateUserAction;
use App\Domain\Users\Actions\SyncUserPermissionsAction;
use App\Domain\Users\Actions\UpdateUserAction;
use App\Domain\Users\Models\Role;
use App\Domain\Users\Models\User;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\SyncUserPermissionsRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Services\LookupCacheService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

final class UserController extends Controller
{
    public function __construct(
        private readonly CreateUserAction $createUserAction,
        private readonly UpdateUserAction $updateUserAction,
        private readonly SyncUserPermissionsAction $syncUserPermissionsAction,
        private readonly DeactivateUserAction $deactivateUserAction,
        private readonly ActivateUserAction $activateUserAction,
        private readonly LookupCacheService $lookups,
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
                $driver = DB::getDriverName();
                $searchLower = mb_strtolower((string) $search);

                $query->where(function ($q) use ($driver, $search, $searchLower) {
                    if ($driver === 'pgsql') {
                        $q->where('full_name', 'ilike', "%{$search}%")
                            ->orWhere('email', 'ilike', "%{$search}%")
                            ->orWhereHas('institution', function ($iq) use ($search) {
                                $iq->where('name', 'ilike', "%{$search}%")
                                    ->orWhere('code', 'ilike', "%{$search}%");
                            });

                        return;
                    }

                    $q->whereRaw('LOWER(full_name) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereHas('institution', function ($iq) use ($searchLower) {
                            $iq->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                                ->orWhereRaw('LOWER(code) LIKE ?', ["%{$searchLower}%"]);
                        });
                });
            });

        $statsBase = clone $usersQuery;

        $nameDirection = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';
        if ($request->query('sort') === 'name') {
            $usersQuery->orderBy('full_name', $nameDirection);
        } else {
            $usersQuery->orderBy('full_name', 'asc');
        }

        $users = $usersQuery
            ->paginate(20)
            ->withQueryString();

        $institutions = $this->lookups->activeInstitutions();
        $roles = $this->lookups->roles();
        $rolesForUi = $this->lookups->rolesWithPermissions();
        $permissionsGrouped = $this->lookups->permissionsGroupedByCategory();

        $stats = [
            // Keep stats consistent with current filters/search (same dataset as table, without pagination).
            'total_users' => (clone $statsBase)->count(),
            'active_users' => (clone $statsBase)->where('is_active', true)->count(),
            'inactive_users' => (clone $statsBase)->where('is_active', false)->count(),
        ];

        // Preload direct (custom) permissions for the current page in one query,
        // avoiding N+1 – one extra JOIN instead of one query per user row.
        $pageUserIds = collect($users->items())->pluck('id')->all();
        $directPermissionIdsByUser = DB::table('model_has_permissions')
            ->whereIn('model_id', $pageUserIds)
            ->where('model_type', (new User)->getMorphClass())
            ->select(['model_id', 'permission_id'])
            ->get()
            ->groupBy('model_id')
            ->map(fn ($rows) => $rows->pluck('permission_id')->map(fn ($id): int => (int) $id)->values()->all());

        $userRowPayloads = collect($users->items())->mapWithKeys(
            fn (User $u): array => [
                $u->id => $this->userTableRowPayload(
                    $u,
                    $directPermissionIdsByUser->get($u->id, []),
                ),
            ]
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

        return view('users.create', [
            'institutions' => $this->lookups->activeInstitutionsByType(),
            'roles' => $this->lookups->rolesWithPermissionCount(),
            'permissions' => $this->lookups->permissionsGroupedByCategory(),
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
                    'message' => __("L'utilisateur « :name » a été créé avec succès.", ['name' => $user->full_name]),
                    'user' => $this->userPayloadForTable($user->fresh(['institution', 'roles'])),
                ], 201);
            }

            return redirect()
                ->route('users.show', $user)
                ->with('success', __("L'utilisateur « :name » a été créé avec succès.", ['name' => $user->full_name]));

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()
                ->withInput()
                ->with('error', __('Erreur : :message', ['message' => $e->getMessage()]));
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

        return view('users.edit', [
            'user' => $user,
            'institutions' => $this->lookups->activeInstitutionsByType(),
        ]);
    }

    /**
     * Update the specified user.
     */
    public function update(UpdateUserRequest $request, User $user): RedirectResponse|JsonResponse
    {
        try {
            $user = $this->updateUserAction->execute($user, $request->validated());

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => __("L'utilisateur « :name » a été mis à jour.", ['name' => $user->full_name]),
                    'user' => $this->userPayloadForTable($user->fresh(['institution', 'roles'])),
                ]);
            }

            return redirect()
                ->route('users.show', $user)
                ->with('success', __("L'utilisateur « :name » a été mis à jour.", ['name' => $user->full_name]));

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()
                ->withInput()
                ->with('error', __('Erreur : :message', ['message' => $e->getMessage()]));
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

        $roles = $this->lookups->rolesWithPermissionDetails();
        $allPermissions = $this->lookups->permissionsGroupedByCategory();

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
                    'message' => __('Les rôles et permissions de « :name » ont été mis à jour.', ['name' => $updated->full_name]),
                    'user' => $this->userPayloadForTable($updated->fresh(['institution', 'roles'])),
                ]);
            }

            return redirect()
                ->route('users.show', $user)
                ->with('success', __('Les rôles et permissions de « :name » ont été mis à jour.', ['name' => $updated->full_name]));

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()
                ->withInput()
                ->with('error', __('Erreur : :message', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Deactivate a user.
     */
    public function deactivate(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $this->authorize('user.deactivate');

        try {
            $this->deactivateUserAction->execute($user, auth()->id());

            if ($request->wantsJson()) {
                $fresh = $user->fresh(['institution', 'roles']);

                return response()->json([
                    'message' => __("L'utilisateur « :name » a été désactivé.", ['name' => $fresh->full_name]),
                    'user' => $this->userPayloadForTable($fresh),
                ]);
            }

            return back()->with('success', __("L'utilisateur « :name » a été désactivé.", ['name' => $user->full_name]));

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', __('Erreur : :message', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Reactivate a user.
     */
    public function activate(Request $request, User $user): RedirectResponse|JsonResponse
    {
        $this->authorize('user.deactivate');

        try {
            $fresh = $this->activateUserAction->execute($user);

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => __("L'utilisateur « :name » a été réactivé.", ['name' => $fresh->full_name]),
                    'user' => $this->userPayloadForTable($fresh),
                ]);
            }

            return back()->with('success', __("L'utilisateur « :name » a été réactivé.", ['name' => $user->full_name]));
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', __('Erreur : :message', ['message' => $e->getMessage()]));
        }
    }

    /**
     * Serialize a user row for the JS table payload.
     *
     * @param  array<int>  $preloadedDirectPermissionIds  Pre-fetched direct permission IDs
     *                                                    (avoids a per-row DB query).
     * @return array<string, mixed>
     */
    private function userPayloadForTable(User $user, array $preloadedDirectPermissionIds = []): array
    {
        $user->loadMissing(['institution:id,name,code', 'roles:id,name,slug']);

        $directPermIds = $preloadedDirectPermissionIds !== []
            ? $preloadedDirectPermissionIds
            : $user->getDirectPermissions()->pluck('id')->map(fn ($id): int => (int) $id)->values()->all();

        return $this->userTableRowPayload($user, $directPermIds);
    }

    /**
     * @param  array<int>  $directPermIds
     * @return array<string, mixed>
     */
    private function userTableRowPayload(User $user, array $directPermIds): array
    {
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
            'custom_permission_ids' => $directPermIds,
        ];
    }
}
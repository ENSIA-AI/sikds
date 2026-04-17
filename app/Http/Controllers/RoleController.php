<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Users\Actions\CreateRoleAction;
use App\Domain\Users\Actions\DeleteRoleAction;
use App\Domain\Users\Actions\UpdateRoleAction;
use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Support\Permissions\PermissionSections;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class RoleController extends Controller
{
    public function __construct(
        private readonly CreateRoleAction $createRoleAction,
        private readonly UpdateRoleAction $updateRoleAction,
        private readonly DeleteRoleAction $deleteRoleAction,
    ) {
    }

    // Display a listing of roles.
    public function index(Request $request): View
    {
        $this->authorize('role.view');
        
        $roles = Role::query()
            ->with('permissions:id,name,code')
            ->withCount(['users', 'permissions'])
            ->when($request->search, function ($query, $search) {
                $query->where('name', 'ilike', "%{$search}%")
                    ->orWhere('description', 'ilike', "%{$search}%");
            })
            ->orderBy('is_system_role', 'desc')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
        
        $permissionsGrouped = PermissionSections::groupedForModal(
            Permission::query()->orderBy('name')->get()
        );

        return view('roles', [
            'roles' => $roles,
            'search' => $request->search,
            'permissionsGrouped' => $permissionsGrouped,
        ]);
    }

    // Show the form for creating a new role.
    public function create(): View
    {
        $this->authorize('role.create');
        
        $permissions = Permission::query()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');
        
        return view('roles.create', [
            'permissions' => $permissions,
        ]);
    }

    // Store a newly created role.
    public function store(StoreRoleRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $role = $this->createRoleAction->execute(
                $request->validated(),
                $request->user()->id
            );

            $role->loadCount('permissions');

            if ($request->wantsJson()) {
                $cardHtml = view('components.role-card', [
                    'roleName' => $role->name,
                    'permissionCount' => $role->permissions_count,
                    'description' => $role->description ?? '',
                    'icon' => 'shield',
                    'href' => route('roles.show', $role),
                ])->render();

                return response()->json([
                    'message' => "Le rôle « {$role->name} » a été créé avec succès.",
                    'role' => [
                        'id' => $role->id,
                        'name' => $role->name,
                        'description' => $role->description,
                        'permission_count' => $role->permissions_count,
                    ],
                    'html' => $cardHtml,
                ]);
            }

            return redirect()
                ->route('roles.show', $role)
                ->with('success', "Le rôle « {$role->name} » a été créé avec succès.");
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => "Erreur lors de la création du rôle : {$e->getMessage()}",
                ], 500);
            }

            return back()
                ->withInput()
                ->with('error', "Erreur lors de la création du rôle : {$e->getMessage()}");
        }
    }

    // Display the specified role.
    public function show(Role $role): View
    {
        $this->authorize('role.view');
        
        $role->load([
            'permissions' => fn($q) => $q->orderBy('category')->orderBy('name'),
            'users:id,full_name,email,institution_id',
            'users.institution:id,name',
        ]);
        
        return view('roles.show', [
            'role' => $role,
        ]);
    }

    // Show the form for editing the specified role.
    public function edit(Role $role): View
    {
        $this->authorize('role.edit');
        
        if ($role->is_system_role) {
            abort(403, 'Les rôles système ne peuvent pas être modifiés.');
        }
        
        $permissions = Permission::query()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');
        
        $role->load('permissions');
        
        return view('roles.edit', [
            'role' => $role,
            'permissions' => $permissions,
        ]);
    }

    // Update the specified role.
    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        try {
            $role = $this->updateRoleAction->execute($role, $request->validated());
            
            return redirect()
                ->route('roles.show', $role)
                ->with('success', "Le rôle « {$role->name} » a été mis à jour.");
                
        } catch (\Exception $e) {
            return back()
                ->withInput()
                ->with('error', "Erreur : {$e->getMessage()}");
        }
    }

    // Remove the specified role.
    public function destroy(Role $role): RedirectResponse
    {
        $this->authorize('role.delete');
        
        try {
            $roleName = $role->name;
            $this->deleteRoleAction->execute($role);
            
            return redirect()
                ->route('roles.index')
                ->with('success', "Le rôle « {$roleName} » a été supprimé.");
                
        } catch (\Exception $e) {
            return back()
                ->with('error', "Erreur : {$e->getMessage()}");
        }
    }
}
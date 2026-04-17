<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Users\Actions\AssignRolesToUserAction;
use App\Domain\Users\Actions\UpdateUserAction;
use App\Domain\Users\Models\Institution;
use App\Domain\Users\Models\Role;
use App\Domain\Users\Models\User;
use App\Http\Requests\AssignRolesRequest;
use App\Http\Requests\UpdateUserRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class UserController extends Controller
{
    public function __construct(
        private readonly UpdateUserAction $updateUserAction,
        private readonly AssignRolesToUserAction $assignRolesToUserAction,
    ) {
    }

    // Display a listing of users.
    public function index(Request $request): View
    {
        $this->authorize('user.view.all');
        
        $users = User::query()
            ->with(['institution:id,name', 'roles:id,name'])
            ->when($request->institution_id, function ($query, $institutionId) {
                $query->where('institution_id', $institutionId);
            })
            ->when($request->is_active !== null, function ($query) use ($request) {
                $query->where('is_active', $request->boolean('is_active'));
            })
            ->when($request->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('full_name', 'ilike', "%{$search}%")
                      ->orWhere('email', 'ilike', "%{$search}%");
                });
            })
            ->orderBy('full_name')
            ->paginate(20)
            ->withQueryString();
        
        $institutions = Institution::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return view('users.index', [
            'users' => $users,
            'institutions' => $institutions,
            'filters' => [
                'institution_id' => $request->institution_id,
                'is_active' => $request->is_active,
                'search' => $request->search,
            ],
        ]);
    }

    // Display the specified user.
    public function show(User $user): View
    {
        $this->authorize('user.view.all');
        
        $user->load([
            'institution',
            'roles.permissions',
        ]);
        
        return view('users.show', [
            'user' => $user,
        ]);
    }

    // Show the form for editing the specified user.

    
    public function edit(User $user): View
    {
        $this->authorize('user.manage');
        
        $institutions = Institution::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        
        return view('users.edit', [
            'user' => $user,
            'institutions' => $institutions,
        ]);
    }

    // Update the specified user.
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

    // Show the form for assigning roles to a user.
    public function editRoles(User $user): View
    {
        $this->authorize('user.assign.permissions');
        
        $roles = Role::query()
            ->withCount('permissions')
            ->orderBy('is_system_role', 'desc')
            ->orderBy('name')
            ->get();
        
        $user->load('roles');
        
        return view('users.edit-roles', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    // Assign roles to a user.
    public function updateRoles(AssignRolesRequest $request, User $user): RedirectResponse
    {
        try {
            $this->assignRolesToUserAction->execute(
                $user,
                $request->input('role_ids'),
                $request->user()->id
            );
            
            return redirect()
                ->route('users.show', $user)
                ->with('success', "Les rôles de « {$user->full_name} » ont été mis à jour.");
                
        } catch (\Exception $e) {
            return back()
                ->with('error', "Erreur : {$e->getMessage()}");
        }
    }

    // Deactivate a user.
    public function deactivate(User $user): RedirectResponse
    {
        $this->authorize('user.deactivate');
        
        // Prevent deactivating yourself
        if ($user->id === auth()->id()) {
            return back()->with('error', 'Vous ne pouvez pas vous désactiver vous-même.');
        }
        
        $user->update(['is_active' => false]);
        
        return back()->with('success', "L'utilisateur « {$user->full_name} » a été désactivé.");
    }

    // Reactivate a user.
    public function activate(User $user): RedirectResponse
    {
        $this->authorize('user.manage');
        
        $user->update(['is_active' => true]);
        
        return back()->with('success', "L'utilisateur « {$user->full_name} » a été réactivé.");
    }
}
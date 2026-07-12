<?php

use App\Domain\Users\Models\Institution;
use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use App\Domain\Users\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->withoutVite();

    $this->institution = Institution::factory()->create([
        'name' => 'Test Institution',
        'code' => 'TEST',
        'is_active' => true,
    ]);

    $this->adminRole = Role::factory()->create([
        'name' => 'Administrator',
        'slug' => 'administrator',
        'is_system_role' => false,
    ]);

    $this->userRole = Role::factory()->create([
        'name' => 'User',
        'slug' => 'user',
        'is_system_role' => false,
    ]);

    // Spatie + `authorize('user.view.all')` expect `permissions.name` (or matching `code`) to equal the gate slug.
    $permissions = [
        Permission::factory()->create(['code' => 'user.view.all', 'name' => 'user.view.all', 'category' => 'users']),
        Permission::factory()->create(['code' => 'user.manage', 'name' => 'user.manage', 'category' => 'users']),
        Permission::factory()->create(['code' => 'user.assign.permissions', 'name' => 'user.assign.permissions', 'category' => 'users']),
        Permission::factory()->create(['code' => 'user.deactivate', 'name' => 'user.deactivate', 'category' => 'users']),
    ];

    $this->adminRole->permissions()->attach(collect($permissions)->pluck('id'));

    // Deterministic, non-colliding identity. The list page renders the acting user
    // too, so a random faker name/username/email here can coincidentally match a
    // search term (e.g. "John") or be a substring of another row, making the
    // assertSee/assertDontSee assertions below flaky in full-suite runs.
    $this->authUser = User::factory()->create([
        'full_name' => 'Acting Administrator',
        'username' => 'acting.administrator',
        'email' => 'acting.administrator@sikds.test',
        'institution_id' => $this->institution->id,
        'is_active' => true,
    ]);

    $this->authUser->roles()->attach($this->adminRole->id);
});

test('it displays users index page', function () {

    actingAs($this->authUser);

    $response = $this->get(route('users.index'));

    $response->assertOk();
    $response->assertViewIs('users.index');
    $response->assertViewHas('users');
    $response->assertViewHas('stats');
    $response->assertViewHas('roles');
    $response->assertViewHas('institutions');
});

test('it requires permission to view users', function () {

    $unauthorizedUser = User::factory()->create([
        'institution_id' => $this->institution->id,
    ]);

    actingAs($unauthorizedUser);

    $response = $this->get(route('users.index'));

    $response->assertForbidden();
});

test('it can search users by name', function () {

    actingAs($this->authUser);

    User::factory()->create([
        'full_name' => 'John Doe',
        'institution_id' => $this->institution->id,
    ]);

    User::factory()->create([
        'full_name' => 'Jane Smith',
        'institution_id' => $this->institution->id,
    ]);

    $response = $this->get(route('users.index', ['search' => 'John']));

    $response->assertOk();
    $response->assertSee('John Doe');
    $response->assertDontSee('Jane Smith');
});

test('it can search users by email', function () {

    actingAs($this->authUser);

    User::factory()->create([
        'email' => 'john@example.com',
        'institution_id' => $this->institution->id,
    ]);

    User::factory()->create([
        'email' => 'jane@example.com',
        'institution_id' => $this->institution->id,
    ]);

    $response = $this->get(route('users.index', ['search' => 'john@']));

    $response->assertOk();
    $response->assertSee('john@example.com');
    $response->assertDontSee('jane@example.com');
});

test('it can filter users by role', function () {

    actingAs($this->authUser);

    $adminUser = User::factory()->create([
        'full_name' => 'Roxanne Adminsson',
        'institution_id' => $this->institution->id,
    ]);
    $adminUser->roles()->attach($this->adminRole->id);

    $regularUser = User::factory()->create([
        'full_name' => 'Quentin Regularsson',
        'institution_id' => $this->institution->id,
    ]);
    $regularUser->roles()->attach($this->userRole->id);

    $response = $this->get(route('users.index', ['role_id' => $this->adminRole->id]));

    $response->assertOk();
    $response->assertSee($adminUser->full_name);
    $response->assertDontSee($regularUser->full_name);
});

test('it can sort users alphabetically', function () {

    actingAs($this->authUser);

    User::factory()->create([
        'full_name' => 'Zara Ahmed',
        'institution_id' => $this->institution->id,
    ]);

    User::factory()->create([
        'full_name' => 'Ahmed Ali',
        'institution_id' => $this->institution->id,
    ]);

    $response = $this->get(route('users.index', ['sort' => 'name', 'direction' => 'asc']));

    $response->assertOk();
    $paginator = $response->viewData('users');
    $names = collect($paginator->items())->pluck('full_name')->values()->all();
    $ahmedIdx = array_search('Ahmed Ali', $names, true);
    $zaraIdx = array_search('Zara Ahmed', $names, true);
    expect($ahmedIdx)->not->toBeFalse()
        ->and($zaraIdx)->not->toBeFalse()
        ->and($ahmedIdx)->toBeLessThan($zaraIdx);
});

test('it creates user via json', function () {

    actingAs($this->authUser);

    $payload = [
        'full_name' => 'New User',
        'email' => 'newuser@example.com',
        'institution_id' => $this->institution->id,
        'role_ids' => [$this->userRole->id],
        'permission_ids' => [],
        'auth_type' => 'sso',
    ];

    $response = $this->postJson(route('users.store'), $payload);

    $response->assertCreated();

    $this->assertDatabaseHas('users', [
        'email' => 'newuser@example.com',
        'full_name' => 'New User',
    ]);
});

test('it validates required fields when creating user', function () {

    actingAs($this->authUser);

    $response = $this->postJson(route('users.store'), []);

    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['full_name', 'email', 'institution_id']);
});

test('it validates unique email when creating user', function () {

    actingAs($this->authUser);

    User::factory()->create([
        'email' => 'existing@example.com',
        'institution_id' => $this->institution->id,
    ]);

    $payload = [
        'full_name' => 'New User',
        'email' => 'existing@example.com',
        'institution_id' => $this->institution->id,
        'role_ids' => [$this->userRole->id],
        'permission_ids' => [],
        'auth_type' => 'sso',
    ];

    $response = $this->postJson(route('users.store'), $payload);

    $response->assertStatus(422);
});

test('it updates user permissions via json', function () {

    actingAs($this->authUser);

    $user = User::factory()->create([
        'institution_id' => $this->institution->id,
    ]);

    $user->roles()->attach($this->userRole->id);

    $permission = Permission::factory()->create([
        'code' => 'documents.view',
    ]);

    $payload = [
        'role_ids' => [$this->adminRole->id],
        'permission_ids' => [$permission->id],
    ];

    $response = $this->putJson(route('users.update-permissions', $user), $payload);

    $response->assertOk();
});

test('it can deactivate user', function () {

    actingAs($this->authUser);

    $user = User::factory()->create([
        'is_active' => true,
    ]);

    $response = $this->post(route('users.deactivate', $user));

    $response->assertRedirect();

    $user->refresh();

    expect($user->is_active)->toBeFalse();
});

test('it can activate user', function () {

    actingAs($this->authUser);

    $user = User::factory()->create([
        'is_active' => false,
    ]);

    $response = $this->post(route('users.activate', $user));

    $response->assertRedirect();

    $user->refresh();

    expect($user->is_active)->toBeTrue();
});
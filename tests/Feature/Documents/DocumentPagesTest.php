<?php

use App\Domain\Users\Models\User;
use App\Models\Permission;
use App\Models\Role;

beforeEach(function (): void {
    config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $this->withoutVite();
});

test('guests are redirected from document pages to login', function () {
    $this->get(route('documents.index'))->assertRedirect('/login');
    $this->get(route('documents.create'))->assertRedirect('/login');
    $this->get(route('documents.show', 'DOC-2024-001'))->assertRedirect('/login');
    $this->get(route('documents.edit', 'DOC-2024-001'))->assertRedirect('/login');
});

test('authenticated users can access the documents list page', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('documents.index'));

    $response->assertOk();
    $response->assertSee('Titre & Référence', false);
    $response->assertSee('Téléverser un Document', false);
});

test('documents list filters by search query', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('documents.index', ['q' => 'Budget Universitaire']));

    $response->assertOk();
    $response->assertSee('Décision Ministérielle sur le Budget Universitaire', false);
    $response->assertDontSee('Rapport Annuel d\'Activité 2023', false);
});

test('documents list filters by date and status', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('documents.index', [
        'status' => ['active'],
        'date_from' => '2024-03-11',
        'date_to' => '2024-03-31',
    ]));

    $response->assertOk();
    $response->assertSee('Directive MESRS – Réforme Pédagogique 2024', false);
    $response->assertDontSee('Décision Ministérielle sur le Budget Universitaire', false);
    $response->assertDontSee('Document Test Supprimé', false);
});

test('documents list includes show and edit links', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('documents.index'));

    $response->assertOk();
    $response->assertSee('/documents/', false);
    $response->assertSee('/edit', false);
});

test('authenticated non-super-admin users cannot access the document show page', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('documents.show', 'DOC-2024-001'));

    $response->assertForbidden();
});

test('super-admin users can access the document show page', function () {
    $user = User::factory()->create();
    Permission::query()->firstOrCreate([
        'name' => 'document.view.all',
        'guard_name' => 'web',
    ], [
        'code' => 'document.view.all',
        'description' => 'Consulter tous les documents',
        'category' => 'documents',
    ]);
    $role = Role::query()->firstOrCreate([
        'name' => 'Super Administrateur',
        'guard_name' => 'web',
    ], [
        'slug' => 'super-admin',
        'is_system_role' => true,
    ]);
    $role->givePermissionTo('document.view.all');
    $user->assignRole($role);
    $this->actingAs($user);

    $response = $this->get(route('documents.show', 'DOC-2024-001'));

    $response->assertOk();
    $response->assertSee('Circulaire MESRS - Réforme Pédagogique 2024', false);
    $response->assertSee('Historique des Versions', false);
    $response->assertSee(route('documents.edit', 'MESRS/DG/2024/045'), false);
});

test('super-admin document show page includes archive and delete confirmation alerts', function () {
    $user = User::factory()->create();
    Permission::query()->firstOrCreate([
        'name' => 'document.view.all',
        'guard_name' => 'web',
    ], [
        'code' => 'document.view.all',
        'description' => 'Consulter tous les documents',
        'category' => 'documents',
    ]);
    $role = Role::query()->firstOrCreate([
        'name' => 'Super Administrateur',
        'guard_name' => 'web',
    ], [
        'slug' => 'super-admin',
        'is_system_role' => true,
    ]);
    $role->givePermissionTo('document.view.all');
    $user->assignRole($role);
    $this->actingAs($user);

    $response = $this->get(route('documents.show', 'DOC-2024-001'));

    $response->assertOk();
    $response->assertSee('Archiver le Document', false);
    $response->assertSee("Confirmer l'archivage", false);
    $response->assertSee('Supprimer le Document', false);
    $response->assertSee('Action irréversible', false);
});

test('authenticated users can access the document edit page', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('documents.edit', 'DOC-2024-001'));

    $response->assertOk();
    $response->assertSee('Modifier le Document', false);
    $response->assertSee('Informations Générales', false);
    $response->assertSee('Nouvelle Version', false);
});

test('document edit page includes cancel confirmation alert', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('documents.edit', 'DOC-2024-001'));

    $response->assertOk();
    $response->assertSee('Annuler les Modifications', false);
    $response->assertSee('Modifications non enregistrées', false);
    $response->assertSee('Continuer à modifier', false);
});

test('authenticated users can access the document upload page', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('documents.create'));

    $response->assertOk();
    $response->assertSee('Téléversement Simple', false);
    $response->assertSee('Téléversement en Lot', false);
});

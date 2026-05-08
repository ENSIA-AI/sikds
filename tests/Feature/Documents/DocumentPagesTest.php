<?php

use App\Domain\Documents\Models\Document;
use App\Domain\Institutions\Models\Institution;
use App\Domain\Users\Models\User;
use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Institution::query()->firstOrCreate(
        ['code' => 'MESRS'],
        ['name' => 'Ministère', 'type' => 'ministry', 'domain' => 'mesrs.dz']
    );
});

function webGrantPermission(User $user, string $code): void
{
    Permission::query()->firstOrCreate(
        ['name' => $code, 'guard_name' => 'web'],
        ['code' => $code, 'description' => $code, 'category' => 'documents']
    );
    $user->givePermissionTo($code);
}

function webEnsureSuperAdmin(User $user): void
{
    $role = Role::query()->firstOrCreate(
        ['name' => 'Super Administrateur', 'guard_name' => 'web'],
        ['slug' => 'super-admin', 'is_system_role' => true]
    );
    webGrantPermission($user, 'document.view.all');
    $role->givePermissionTo('document.view.all');
    $user->assignRole($role);
}

function webCreateDocument(User $uploader, array $overrides = []): Document
{
    $defaults = [
        'reference_number' => now()->format('Y').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
        'title' => 'Document '.Str::random(6),
        'description' => 'Description',
        'file_path' => 'documents/test.pdf',
        'file_hash' => str_repeat('a', 64),
        'file_size' => 1024,
        'issue_date' => now()->toDateString(),
        'effective_date' => now()->toDateString(),
        'expiration_date' => now()->addMonth()->toDateString(),
        'status' => 'active',
        'indexing_status' => 'pending',
        'target_audience' => 'all',
        'version_number' => 1,
        'uploaded_by' => $uploader->id,
    ];

    return Document::query()->create(array_merge($defaults, $overrides));
}

test('guests are redirected from document pages to login', function () {
    $this->get(route('documents.index'))->assertRedirect('/login');
    $this->get(route('documents.create'))->assertRedirect('/login');
    $this->get(route('documents.show', '1'))->assertRedirect('/login');
    $this->get(route('documents.edit', '1'))->assertRedirect('/login');
});

test('authenticated users can access the documents list page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    webCreateDocument($user, ['title' => 'Circulaire active']);

    $response = $this->get(route('documents.index'));

    $response->assertOk();
    $response->assertSee('Titre &amp; Référence', false);
    $response->assertDontSee('Téléverser un Document', false);
    $response->assertSee('Circulaire active', false);
});

test('documents list shows upload button only to users with create permission', function () {
    $user = User::factory()->create();
    webGrantPermission($user, 'document.create');
    $this->actingAs($user);

    $response = $this->get(route('documents.index'));

    $response->assertOk();
    $response->assertSee('Téléverser un Document', false);
});

test('documents list filters by search query', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    webCreateDocument($user, ['title' => 'Budget Universitaire 2026']);
    webCreateDocument($user, ['title' => 'Rapport Annuel 2025']);

    $response = $this->get(route('documents.index', ['q' => 'Budget Universitaire']));

    $response->assertOk();
    $response->assertSee('Budget Universitaire 2026', false);
    $response->assertDontSee('Rapport Annuel 2025', false);
});

test('documents list filters by date and status', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    webCreateDocument($user, ['title' => 'Document actif mars', 'status' => 'active', 'issue_date' => '2026-03-15']);
    webCreateDocument($user, ['title' => 'Document brouillon mars', 'status' => 'draft', 'issue_date' => '2026-03-20']);
    webCreateDocument($user, ['title' => 'Document actif janvier', 'status' => 'active', 'issue_date' => '2026-01-10']);

    $response = $this->get(route('documents.index', [
        'status' => ['active'],
        'date_from' => '2026-03-01',
        'date_to' => '2026-03-31',
    ]));

    $response->assertOk();
    $response->assertSee('Document actif mars', false);
    $response->assertDontSee('Document brouillon mars', false);
    $response->assertDontSee('Document actif janvier', false);
});

test('documents list includes document action links', function () {
    $user = User::factory()->create();
    webGrantPermission($user, 'document.edit');
    webEnsureSuperAdmin($user);
    $this->actingAs($user);
    $document = webCreateDocument($user);

    $response = $this->get(route('documents.index'));

    $response->assertOk();
    $response->assertSee(route('documents.show', $document->id), false);
    $response->assertSee(route('documents.edit', $document->id), false);
    $response->assertSee('open-download-modal', false);
});

test('documents list page includes download traceability warning modal', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $document = webCreateDocument($user, ['status' => 'active']);

    $response = $this->get(route('documents.index'));

    $response->assertOk();
    $response->assertSee('Avertissement de traçabilité du document', false);
    $response->assertSee('open-download-modal', false);
    $response->assertSee($document->reference_number, false);
});

test('authenticated non-super-admin users cannot access the document show page', function () {
    $user = User::factory()->create();
    $document = webCreateDocument($user);
    $this->actingAs($user);

    $response = $this->get(route('documents.show', $document->id));

    $response->assertForbidden();
});

test('super-admin users can access the document show page', function () {
    $user = User::factory()->create();
    webEnsureSuperAdmin($user);
    webGrantPermission($user, 'document.edit');
    $this->actingAs($user);
    $document = webCreateDocument($user, ['title' => 'Circulaire MESRS']);

    $response = $this->get(route('documents.show', $document->id));

    $response->assertOk();
    $response->assertSee('Circulaire MESRS', false);
    $response->assertSee('Historique des Versions', false);
    $response->assertSee(route('documents.edit', $document->id), false);
});

test('super-admin document show page includes archive and delete confirmation alerts', function () {
    $user = User::factory()->create();
    webEnsureSuperAdmin($user);
    webGrantPermission($user, 'document.delete');
    webGrantPermission($user, 'document.publish');
    $this->actingAs($user);
    $document = webCreateDocument($user, ['status' => 'active']);

    $response = $this->get(route('documents.show', $document->id));

    $response->assertOk();
    $response->assertSee('Archiver le Document', false);
    $response->assertSeeText("Confirmer l'archivage");
    $response->assertSee('Supprimer le Document', false);
    $response->assertSee('Action irréversible', false);
});

test('super-admin document show page includes download traceability warning modal', function () {
    $user = User::factory()->create();
    webEnsureSuperAdmin($user);
    $this->actingAs($user);
    $document = webCreateDocument($user, ['status' => 'active']);

    $response = $this->get(route('documents.show', $document->id));

    $response->assertOk();
    $response->assertSee('Avertissement de traçabilité du document', false);
    $response->assertSee('En poursuivant ce téléchargement, ce document officiel sera filigrané de manière permanente', false);
    $response->assertSee('Téléchargement...', false);
});

test('users with edit permission can access the document edit page', function () {
    $user = User::factory()->create();
    webGrantPermission($user, 'document.edit');
    $this->actingAs($user);
    $document = webCreateDocument($user);

    $response = $this->get(route('documents.edit', $document->id));

    $response->assertOk();
    $response->assertSee('Modifier le Document', false);
    $response->assertSee('Informations Générales', false);
    $response->assertSee('Nouvelle Version', false);
});

test('document edit page includes cancel confirmation alert', function () {
    $user = User::factory()->create();
    webGrantPermission($user, 'document.edit');
    $this->actingAs($user);
    $document = webCreateDocument($user);

    $response = $this->get(route('documents.edit', $document->id));

    $response->assertOk();
    $response->assertSee('Annuler les Modifications', false);
    $response->assertSee('Modifications non enregistrées', false);
    $response->assertSee('Continuer à modifier', false);
});

test('authenticated users can access the document upload page', function () {
    $user = User::factory()->create();
    webGrantPermission($user, 'document.create');
    $this->actingAs($user);

    $response = $this->get(route('documents.create'));

    $response->assertOk();
    $response->assertSee('Téléversement Simple', false);
    $response->assertSee('Téléversement en Lot', false);
});

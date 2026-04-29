<?php

use App\Domain\Documents\Models\Document;
use App\Domain\Institutions\Models\Institution;
use App\Domain\Users\Models\User;
use App\Jobs\IndexDocumentJob;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $this->withoutVite();
    config(['filesystems.documents_disk' => 's3']);
    Storage::fake('s3');
    Queue::fake();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Institution::query()->firstOrCreate(
        ['code' => 'MESRS'],
        ['name' => 'Ministère', 'type' => 'ministry', 'domain' => 'mesrs.dz']
    );
});

function grantPermission(User $user, string $code): void
{
    Permission::query()->firstOrCreate(
        ['name' => $code, 'guard_name' => 'web'],
        ['code' => $code, 'description' => $code, 'category' => 'documents']
    );
    $user->givePermissionTo($code);
}

function ensureSuperAdmin(User $user): void
{
    $role = Role::query()->firstOrCreate(
        ['name' => 'Super Administrateur', 'guard_name' => 'web'],
        ['slug' => 'super-admin', 'is_system_role' => true]
    );
    grantPermission($user, 'document.view.all');
    $role->givePermissionTo('document.view.all');
    $user->assignRole($role);
}

function fakePdfUpload(string $name = 'document.pdf'): UploadedFile
{
    $tempPath = tempnam(sys_get_temp_dir(), 'sikds_pdf_');
    file_put_contents(
        $tempPath,
        "%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n"
    );

    return new UploadedFile($tempPath, $name, 'application/pdf', null, true);
}

function seedDocumentsTestTag(): int
{
    return DB::table('tags')->insertGetId([
        'name' => 'Tag test',
        'slug' => 'tag-test-'.Str::random(8),
        'description' => null,
        'color' => '#000000',
        'category' => 'test',
        'parent_id' => null,
        'is_predefined' => false,
        'created_at' => now(),
        'updated_at' => now(),
        'created_by' => null,
    ]);
}

/**
 * @param  array<string, mixed>  $overrides
 */
function createDocument(User $uploader, array $overrides = []): Document
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
        'status' => 'draft',
        'indexing_status' => 'pending',
        'target_audience' => 'all',
        'version_number' => 1,
        'uploaded_by' => $uploader->id,
    ];

    return Document::query()->create(array_merge($defaults, $overrides));
}

test('guests are redirected from documents api routes', function () {
    $this->getJson('/api/documents')->assertStatus(401);
});

test('user with document.view.all can preview document via api show', function () {
    $user = User::factory()->create();
    grantPermission($user, 'document.view.all');
    $this->actingAs($user);

    $doc = createDocument($user, ['status' => 'active']);

    $this->getJson('/api/documents/'.$doc->id)
        ->assertOk()
        ->assertJsonPath('document.id', $doc->id);
});

test('super admin can preview document via api show and versions', function () {
    $user = User::factory()->create();
    ensureSuperAdmin($user);
    $this->actingAs($user);

    $doc = createDocument($user, ['status' => 'active']);

    $this->getJson('/api/documents/'.$doc->id)
        ->assertOk()
        ->assertJsonPath('document.id', $doc->id);

    $this->getJson('/api/documents/'.$doc->id.'/versions')
        ->assertOk()
        ->assertJsonPath('document_id', $doc->id);
});

test('documents api list returns only assigned documents for non super admin', function () {
    $instA = Institution::query()->firstOrCreate(
        ['code' => 'UA'],
        ['name' => 'University A', 'type' => 'university', 'domain' => 'ua.test']
    );
    $instB = Institution::query()->firstOrCreate(
        ['code' => 'UB'],
        ['name' => 'University B', 'type' => 'university', 'domain' => 'ub.test']
    );

    $user = User::factory()->create(['institution_id' => $instA->id]);
    grantPermission($user, 'document.view.own_institution');
    grantPermission($user, 'document.view.assigned');
    $this->actingAs($user);

    $uploader = User::factory()->create();

    $docAll = createDocument($uploader, ['status' => 'active', 'target_audience' => 'all']);
    $docInstA = createDocument($uploader, ['status' => 'active', 'target_audience' => 'specific_institutions']);
    DB::table('document_institution_targets')->insert([
        'document_id' => $docInstA->id,
        'institution_id' => $instA->id,
        'created_at' => now(),
    ]);

    $docInstB = createDocument($uploader, ['status' => 'active', 'target_audience' => 'specific_institutions']);
    DB::table('document_institution_targets')->insert([
        'document_id' => $docInstB->id,
        'institution_id' => $instB->id,
        'created_at' => now(),
    ]);

    $docDirect = createDocument($uploader, ['status' => 'active', 'target_audience' => 'specific_roles']);
    DB::table('document_user_targets')->insert([
        'document_id' => $docDirect->id,
        'user_id' => $user->id,
        'created_at' => now(),
    ]);

    $response = $this->getJson('/api/documents');
    $response->assertOk();

    $titles = collect($response->json('data'))->pluck('title')->all();
    expect($titles)->toContain($docAll->title);
    expect($titles)->toContain($docInstA->title);
    expect($titles)->toContain($docDirect->title);
    expect($titles)->not->toContain($docInstB->title);
});

test('document upload requires create permission', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    $tagId = seedDocumentsTestTag();

    $payload = [
        'files' => [fakePdfUpload()],
        'documents_meta' => [[
            'title' => 'Doc test',
            'description' => null,
            'issue_date' => now()->toDateString(),
            'effective_date' => now()->toDateString(),
            'expiration_date' => now()->addDays(2)->toDateString(),
            'target_audience' => 'all',
            'tag_ids' => [$tagId],
        ]],
    ];

    $this->post('/api/documents', $payload)
        ->assertForbidden();
});

test('document upload enforces pdf only and batch max five', function () {
    $user = User::factory()->create();
    grantPermission($user, 'document.create');
    $this->actingAs($user);
    $tagId = seedDocumentsTestTag();

    $badPayload = [
        'files' => [UploadedFile::fake()->create('bad.txt', 1, 'text/plain')],
        'documents_meta' => [[
            'title' => 'Bad file',
            'issue_date' => now()->toDateString(),
            'target_audience' => 'all',
            'tag_ids' => [$tagId],
        ]],
    ];

    $this->post('/api/documents', $badPayload)
        ->assertSessionHasErrors(['files.0']);

    $manyFiles = [];
    $manyMeta = [];
    for ($i = 0; $i < 6; $i++) {
        $manyFiles[] = fakePdfUpload("doc-{$i}.pdf");
        $manyMeta[] = [
            'title' => "Doc {$i}",
            'issue_date' => now()->toDateString(),
            'target_audience' => 'all',
            'tag_ids' => [$tagId],
        ];
    }

    $this->post('/api/documents', ['files' => $manyFiles, 'documents_meta' => $manyMeta])
        ->assertSessionHasErrors(['files']);
});

test('document upload creates draft document and stores file', function () {
    $user = User::factory()->create();
    grantPermission($user, 'document.create');
    grantPermission($user, 'tag.assign');
    $this->actingAs($user);
    $tagId = seedDocumentsTestTag();

    $payload = [
        'files' => [fakePdfUpload('valid.pdf')],
        'documents_meta' => [[
            'title' => 'Doc uploaded',
            'description' => 'Desc',
            'issue_date' => now()->toDateString(),
            'effective_date' => now()->toDateString(),
            'expiration_date' => now()->addDays(1)->toDateString(),
            'target_audience' => 'all',
            'tag_ids' => [$tagId],
        ]],
    ];

    $response = $this->post('/api/documents', $payload)
        ->assertCreated()
        ->assertJsonPath('documents.0.status', 'draft');

    $id = $response->json('documents.0.id');
    $doc = Document::query()->findOrFail($id);

    expect($doc->status)->toBe('draft');
    expect($doc->indexing_status)->toBe('pending');
    expect($doc->file_path)->not->toBe('');
    Storage::disk((string) config('filesystems.documents_disk'))->assertExists($doc->file_path);
});

test('document upload requires at least one tag', function () {
    $user = User::factory()->create();
    grantPermission($user, 'document.create');
    $this->actingAs($user);

    $payload = [
        'files' => [fakePdfUpload()],
        'documents_meta' => [[
            'title' => 'Doc test',
            'issue_date' => now()->toDateString(),
            'target_audience' => 'all',
            'tag_ids' => [],
        ]],
    ];

    $this->post('/api/documents', $payload)
        ->assertSessionHasErrors(['documents_meta.0.tag_ids']);
});

test('publish endpoint enforces draft to active transition', function () {
    $user = User::factory()->create();
    grantPermission($user, 'document.publish');
    $this->actingAs($user);

    $draft = createDocument($user, ['status' => 'draft']);
    $this->postJson('/api/documents/'.$draft->id.'/publish')
        ->assertOk()
        ->assertJsonPath('document.status', 'active');
    $draft->refresh();
    expect($draft->indexing_status)->toBe('pending');
    Queue::assertPushed(IndexDocumentJob::class, fn (IndexDocumentJob $job): bool => true);

    $active = createDocument($user, ['status' => 'active']);
    $this->postJson('/api/documents/'.$active->id.'/publish')
        ->assertStatus(422);
});

test('archive removes document chunks from vector corpus', function () {
    $user = User::factory()->create();
    grantPermission($user, 'document.publish');
    $this->actingAs($user);

    $doc = createDocument($user, ['status' => 'active', 'indexing_status' => 'indexed']);
    DB::table('document_chunks')->insert([
        'document_id' => $doc->id,
        'chunk_index' => 0,
        'content' => 'Old indexed content',
        'metadata' => json_encode(['document_title' => $doc->title]),
        'embedding' => null,
        'created_at' => now(),
    ]);

    $this->postJson('/api/documents/'.$doc->id.'/archive')->assertOk();
    $doc->refresh();

    expect($doc->status)->toBe('archived');
    expect($doc->indexing_status)->toBe('failed');
    expect(DB::table('document_chunks')->where('document_id', $doc->id)->count())->toBe(0);
});

test('soft delete removes document chunks from vector corpus', function () {
    $owner = User::factory()->create();
    $doc = createDocument($owner, ['status' => 'active', 'indexing_status' => 'indexed']);
    DB::table('document_chunks')->insert([
        'document_id' => $doc->id,
        'chunk_index' => 0,
        'content' => 'Active indexed chunk',
        'metadata' => json_encode(['document_title' => $doc->title]),
        'embedding' => null,
        'created_at' => now(),
    ]);

    $deleter = User::factory()->create();
    grantPermission($deleter, 'document.delete');
    $this->actingAs($deleter);

    $this->deleteJson('/api/documents/'.$doc->id)->assertOk();
    $doc->refresh();

    expect($doc->status)->toBe('soft_deleted');
    expect($doc->indexing_status)->toBe('failed');
    expect(DB::table('document_chunks')->where('document_id', $doc->id)->count())->toBe(0);
});

test('updating active document with a new file purges old chunks and requeues indexing', function () {
    $editor = User::factory()->create();
    grantPermission($editor, 'document.edit');
    $this->actingAs($editor);
    $tagId = seedDocumentsTestTag();

    $doc = createDocument($editor, [
        'status' => 'active',
        'indexing_status' => 'indexed',
        'target_audience' => 'all',
    ]);
    DB::table('document_tags')->insert([
        'document_id' => $doc->id,
        'tag_id' => $tagId,
        'assigned_at' => now(),
        'assigned_by' => $editor->id,
    ]);
    DB::table('document_chunks')->insert([
        'document_id' => $doc->id,
        'chunk_index' => 0,
        'content' => 'Stale chunk',
        'metadata' => json_encode(['document_title' => $doc->title]),
        'embedding' => null,
        'created_at' => now(),
    ]);

    $payload = [
        'title' => $doc->title,
        'issue_date' => now()->toDateString(),
        'target_audience' => 'all',
        'tag_ids' => [$tagId],
        'file' => fakePdfUpload('new-version.pdf'),
    ];

    $this->putJson('/api/documents/'.$doc->id, $payload)->assertOk();
    $doc->refresh();

    expect($doc->indexing_status)->toBe('pending');
    expect(DB::table('document_chunks')->where('document_id', $doc->id)->count())->toBe(0);
    Queue::assertPushed(IndexDocumentJob::class, fn (IndexDocumentJob $job): bool => true);
});

test('soft delete and restore require correct permissions', function () {
    $owner = User::factory()->create();
    $doc = createDocument($owner, ['status' => 'active']);

    $deleter = User::factory()->create();
    grantPermission($deleter, 'document.delete');
    $this->actingAs($deleter);

    $this->deleteJson('/api/documents/'.$doc->id)
        ->assertOk();

    $doc->refresh();
    expect($doc->status)->toBe('soft_deleted');
    expect($doc->deleted_at)->not->toBeNull();

    $restorer = User::factory()->create();
    grantPermission($restorer, 'document.restore');
    $this->actingAs($restorer);
    $this->postJson('/api/documents/'.$doc->id.'/restore')
        ->assertOk();

    $doc->refresh();
    expect($doc->status)->not->toBe('soft_deleted');
    expect($doc->deleted_at)->toBeNull();
});


<?php

use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Services\WatermarkService;
use App\Domain\Institutions\Models\Institution;
use App\Domain\Users\Models\User;
use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use Illuminate\Support\Facades\DB;
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

function dlGrantPermission(User $user, string $code): void
{
    Permission::query()->firstOrCreate(
        ['name' => $code, 'guard_name' => 'web'],
        ['code' => $code, 'description' => $code, 'category' => 'documents']
    );
    $user->givePermissionTo($code);
}

function dlEnsureSuperAdmin(User $user): void
{
    $role = Role::query()->firstOrCreate(
        ['name' => 'Super Administrateur', 'guard_name' => 'web'],
        ['slug' => 'super-admin', 'is_system_role' => true]
    );
    dlGrantPermission($user, 'document.view.all');
    $role->givePermissionTo('document.view.all');
    $user->assignRole($role);
}

function dlCreateDocument(User $uploader, array $overrides = []): Document
{
    $defaults = [
        'reference_number' => now()->format('Y').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
        'title'            => 'Document '.Str::random(6),
        'description'      => 'Description',
        'file_path'        => 'documents/test.pdf',
        'file_hash'        => str_repeat('a', 64),
        'file_size'        => 1024,
        'issue_date'       => now()->toDateString(),
        'effective_date'   => now()->toDateString(),
        'expiration_date'  => now()->addMonth()->toDateString(),
        'status'           => 'active',
        'indexing_status'  => 'pending',
        'target_audience'  => 'all',
        'version_number'   => 1,
        'uploaded_by'      => $uploader->id,
    ];

    return Document::query()->create(array_merge($defaults, $overrides));
}

// --- Access control ---

test('guests are redirected from the download route to login', function () {
    $this->get(route('documents.download', 1))->assertRedirect('/login');
});

test('users cannot download a draft document they do not own', function () {
    $user     = User::factory()->create();
    $document = dlCreateDocument($user, ['status' => 'draft']);
    $this->actingAs($user);

    // uploader can access their own draft (isAccessibleBy returns true for uploader)
    // create a *different* user who has no rights
    $other = User::factory()->create();
    $this->actingAs($other);

    $this->get(route('documents.download', $document->id))->assertForbidden();
});

test('users cannot download documents restricted to roles they do not have', function () {
    $uploader = User::factory()->create();
    $document = dlCreateDocument($uploader, [
        'status'          => 'active',
        'target_audience' => 'specific_roles',
    ]);

    $allowedRole = Role::query()->firstOrCreate(
        ['name' => 'Document Restricted Role', 'guard_name' => 'web'],
        ['slug' => 'document-restricted-role', 'is_system_role' => false]
    );

    DB::table('document_role_targets')->insert([
        'document_id' => $document->id,
        'role_id'     => $allowedRole->id,
        'created_at'  => now(),
    ]);

    $unauthorized = User::factory()->create();
    $this->actingAs($unauthorized);

    $this->get(route('documents.download', $document->id))->assertForbidden();
});

test('users cannot download a deleted document they do not own', function () {
    $uploader = User::factory()->create();
    $document = dlCreateDocument($uploader, ['status' => 'deleted']);

    $otherUser = User::factory()->create();
    $this->actingAs($otherUser);

    $this->get(route('documents.download', $document->id))->assertForbidden();
});

test('authorized users receive a watermarked pdf download', function () {
    $user     = User::factory()->create();
    dlEnsureSuperAdmin($user);
    $this->actingAs($user);

    $document = dlCreateDocument($user, [
        'reference_number' => '2026-WM-TEST',
        'status'           => 'active',
        'target_audience'  => 'all',
    ]);

    // Create a real temp file so Symfony BinaryFileResponse can resolve it
    $fakePdf = tempnam(sys_get_temp_dir(), 'test_wm_').'.pdf';
    file_put_contents($fakePdf, '%PDF-1.4 1 0 obj<</Type/Catalog>>endobj');

    $this->mock(WatermarkService::class, function ($mock) use ($fakePdf) {
        $mock->shouldReceive('generateWatermarkedPdf')
            ->once()
            ->andReturn($fakePdf);
    });

    $response = $this->get(route('documents.download', $document->id));

    $response->assertDownload('2026-WM-TEST.pdf');

    @unlink($fakePdf);
});

test('download creates a download_log record', function () {
    $user     = User::factory()->create();
    dlEnsureSuperAdmin($user);
    $this->actingAs($user);

    $document = dlCreateDocument($user, ['status' => 'active', 'target_audience' => 'all']);

    $fakePdf = tempnam(sys_get_temp_dir(), 'test_wm_').'.pdf';
    file_put_contents($fakePdf, '%PDF-1.4 1 0 obj<</Type/Catalog>>endobj');

    $this->mock(WatermarkService::class, function ($mock) use ($fakePdf) {
        $mock->shouldReceive('generateWatermarkedPdf')->once()->andReturn($fakePdf);
    });

    $this->get(route('documents.download', $document->id));

    $this->assertDatabaseHas('download_logs', [
        'document_id' => $document->id,
        'user_id'     => $user->id,
    ]);

    @unlink($fakePdf);
});

test('download creates an audit_log entry', function () {
    $user     = User::factory()->create();
    dlEnsureSuperAdmin($user);
    $this->actingAs($user);

    $document = dlCreateDocument($user, ['status' => 'active', 'target_audience' => 'all']);

    $fakePdf = tempnam(sys_get_temp_dir(), 'test_wm_').'.pdf';
    file_put_contents($fakePdf, '%PDF-1.4 1 0 obj<</Type/Catalog>>endobj');

    $this->mock(WatermarkService::class, function ($mock) use ($fakePdf) {
        $mock->shouldReceive('generateWatermarkedPdf')->once()->andReturn($fakePdf);
    });

    $this->get(route('documents.download', $document->id));

    $this->assertDatabaseHas('audit_logs', [
        'event_type' => 'document.download',
        'user_id'    => $user->id,
        'result'     => 'success',
    ]);

    @unlink($fakePdf);
});

test('download_log has a watermark_uuid set after download', function () {
    $user     = User::factory()->create();
    dlEnsureSuperAdmin($user);
    $this->actingAs($user);

    $document = dlCreateDocument($user, ['status' => 'active', 'target_audience' => 'all']);

    $fakePdf = tempnam(sys_get_temp_dir(), 'test_wm_').'.pdf';
    file_put_contents($fakePdf, '%PDF-1.4 1 0 obj<</Type/Catalog>>endobj');

    $this->mock(WatermarkService::class, function ($mock) use ($fakePdf) {
        $mock->shouldReceive('generateWatermarkedPdf')->once()->andReturn($fakePdf);
    });

    $this->get(route('documents.download', $document->id));

    $log = \App\Domain\Documents\Models\DownloadLog::where('document_id', $document->id)
        ->where('user_id', $user->id)
        ->first();

    expect($log)->not->toBeNull();
    expect($log->watermark_uuid)->toMatch('/^[0-9a-f-]{36}$/');

    @unlink($fakePdf);
});

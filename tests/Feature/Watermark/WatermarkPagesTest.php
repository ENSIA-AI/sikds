<?php

use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DownloadLog;
use App\Domain\Institutions\Models\Institution;
use App\Domain\Users\Models\User;
use App\Domain\Users\Models\Permission;
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

function wmGrantPermission(User $user, string $code): void
{
    Permission::query()->firstOrCreate(
        ['name' => $code, 'guard_name' => 'web'],
        ['code' => $code, 'description' => $code, 'category' => 'audit']
    );
    $user->givePermissionTo($code);
}

function wmCreateDocument(User $uploader, array $overrides = []): Document
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

function wmCreateDownloadLog(User $user, Document $document): DownloadLog
{
    return DownloadLog::create([
        'document_id' => $document->id,
        'user_id'     => $user->id,
        'ip_address'  => '127.0.0.1',
        'user_agent'  => 'TestBrowser/1.0',
    ]);
}

// --- Access control ---

test('guests are redirected from watermark pages to login', function () {
    $this->get(route('watermark.index'))->assertRedirect('/login');
    $this->get(route('watermark.show', Str::uuid()))->assertRedirect('/login');
});

test('users without audit.view cannot access the watermark index', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('watermark.index'))->assertForbidden();
});

test('users without audit.view cannot access the watermark show page', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->get(route('watermark.show', Str::uuid()))->assertForbidden();
});

// --- Index page ---

test('users with audit.view can access the watermark index', function () {
    $user = User::factory()->create();
    wmGrantPermission($user, 'audit.view');
    $this->actingAs($user);

    $this->get(route('watermark.index'))->assertOk();
});

test('watermark index displays a download log entry', function () {
    $user = User::factory()->create();
    wmGrantPermission($user, 'audit.view');
    $this->actingAs($user);

    $document = wmCreateDocument($user, ['title' => 'Rapport Confidentiel']);
    $log      = wmCreateDownloadLog($user, $document);
    $wmCode   = 'WM-'.now()->format('Y').'-'.strtoupper(substr(str_replace('-', '', $log->watermark_uuid), 0, 8));

    $response = $this->get(route('watermark.index'));

    $response->assertOk();
    $response->assertSee($wmCode, false);
    $response->assertSee($user->full_name, false);
});

test('watermark index shows empty state when there are no logs', function () {
    $user = User::factory()->create();
    wmGrantPermission($user, 'audit.view');
    $this->actingAs($user);

    $response = $this->get(route('watermark.index'));

    $response->assertOk();
    $response->assertSee('Aucun téléchargement trouvé.', false);
});

// --- Show page ---

test('watermark show redirects to index when uuid is not found', function () {
    $user = User::factory()->create();
    wmGrantPermission($user, 'audit.view');
    $this->actingAs($user);

    $this->get(route('watermark.show', (string) Str::uuid()))
        ->assertRedirect(route('watermark.index'));
});

test('watermark show displays traceability detail for a valid uuid', function () {
    $user = User::factory()->create();
    wmGrantPermission($user, 'audit.view');
    $this->actingAs($user);

    $document = wmCreateDocument($user, ['title' => 'Circulaire Sécurisée']);
    $log      = wmCreateDownloadLog($user, $document);
    $wmCode   = 'WM-'.now()->format('Y').'-'.strtoupper(substr(str_replace('-', '', $log->watermark_uuid), 0, 8));

    $response = $this->get(route('watermark.show', $log->watermark_uuid));

    $response->assertOk();
    $response->assertSee($wmCode, false);
    $response->assertSee($user->full_name, false);
    $response->assertSee('Circulaire Sécurisée', false);
});

test('watermark show contains the downloader ip address', function () {
    $user = User::factory()->create();
    wmGrantPermission($user, 'audit.view');
    $this->actingAs($user);

    $document = wmCreateDocument($user);
    $log      = wmCreateDownloadLog($user, $document);

    $response = $this->get(route('watermark.show', $log->watermark_uuid));

    $response->assertOk();
    $response->assertSee('127.0.0.1', false);
});

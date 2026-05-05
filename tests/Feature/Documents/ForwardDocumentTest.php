<?php

declare(strict_types=1);

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Documents\Actions\ForwardDocumentToUserAction;
use App\Domain\Documents\Exceptions\DocumentForwardException;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Services\WatermarkService;
use App\Domain\Institutions\Models\Institution;
use App\Domain\Notifications\Models\Notification;
use App\Domain\Users\Models\User;
use App\Jobs\Notifications\SendNotificationEmailJob;
use App\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $this->withoutVite();
    Queue::fake();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    Institution::query()->firstOrCreate(
        ['code' => 'MESRS'],
        ['name' => 'Ministère', 'type' => 'ministry', 'domain' => 'mesrs.dz']
    );
});

function fwdGrantPermission(User $user, string $code): void
{
    Permission::query()->firstOrCreate(
        ['name' => $code, 'guard_name' => 'web'],
        ['code' => $code, 'description' => $code, 'category' => 'documents']
    );
    $user->givePermissionTo($code);
}

/**
 * @param  array<string, mixed>  $overrides
 */
function fwdMakeDocument(User $uploader, array $overrides = []): Document
{
    $defaults = [
        'reference_number' => now()->format('Y').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
        'title' => 'Doc '.Str::random(6),
        'description' => 'Description',
        'file_path' => 'documents/test.pdf',
        'file_hash' => str_repeat('a', 64),
        'file_size' => 1024,
        'issue_date' => now()->toDateString(),
        'effective_date' => now()->toDateString(),
        'expiration_date' => now()->addMonth()->toDateString(),
        'status' => 'active',
        'indexing_status' => 'indexed',
        'target_audience' => 'all',
        'version_number' => 1,
        'uploaded_by' => $uploader->id,
    ];

    return Document::query()->create(array_merge($defaults, $overrides));
}

it('lets an authorized user forward an active document and notifies the recipient', function (): void {
    $sender = User::factory()->create();
    fwdGrantPermission($sender, 'document.forward');
    $recipient = User::factory()->create();
    $document = fwdMakeDocument($sender);

    $this->actingAs($sender);

    $response = $this->postJson(route('documents.forward.store', $document->id), [
        'recipient_id' => $recipient->id,
    ]);

    $response->assertOk();
    expect($response->json('was_already_targeted'))->toBeFalse();

    expect(DB::table('document_user_targets')
        ->where('document_id', $document->id)
        ->where('user_id', $recipient->id)
        ->where('assigned_by', $sender->id)
        ->exists())->toBeTrue();

    expect(Notification::query()
        ->where('type', 'document.forwarded')
        ->where('recipient_user_id', $recipient->id)
        ->where('document_id', $document->id)
        ->exists())->toBeTrue();

    expect(AuditLog::query()
        ->where('event_type', 'document.forwarded')
        ->where('resource_type', 'document')
        ->where('resource_id', $document->id)
        ->exists())->toBeTrue();

    Queue::assertPushed(SendNotificationEmailJob::class);
});

it('makes the forwarded document visible to the recipient afterwards', function (): void {
    $sender = User::factory()->create();
    fwdGrantPermission($sender, 'document.forward');

    // Recipient otherwise has no path to view this document (no view.all permission,
    // different institution, no role match) — only the direct user target unlocks it.
    $otherInst = Institution::query()->firstOrCreate(
        ['code' => 'OTHER'],
        ['name' => 'Other Institution', 'type' => 'university', 'domain' => 'other.test']
    );
    $recipient = User::factory()->create(['institution_id' => $otherInst->id]);
    fwdGrantPermission($recipient, 'document.view.assigned');

    $document = fwdMakeDocument($sender, ['target_audience' => 'specific_users']);

    $this->actingAs($sender);
    $this->postJson(route('documents.forward.store', $document->id), [
        'recipient_id' => $recipient->id,
    ])->assertOk();

    expect($document->refresh()->isAccessibleBy($recipient))->toBeTrue();

    $this->actingAs($recipient);
    $list = $this->getJson('/api/documents')->assertOk();
    $titles = collect($list->json('data'))->pluck('title')->all();
    expect($titles)->toContain($document->title);
});

it('lets the recipient download the forwarded document', function (): void {
    $sender = User::factory()->create();
    fwdGrantPermission($sender, 'document.forward');

    $recipient = User::factory()->create();
    $document = fwdMakeDocument($sender, [
        'reference_number' => '2026-FWD-DL-TEST',
        'target_audience' => 'specific_users',
    ]);

    $this->actingAs($sender);
    $this->postJson(route('documents.forward.store', $document->id), [
        'recipient_id' => $recipient->id,
    ])->assertOk();

    $fakePdf = tempnam(sys_get_temp_dir(), 'test_fwd_dl_').'.pdf';
    file_put_contents($fakePdf, '%PDF-1.4 1 0 obj<</Type/Catalog>>endobj');
    $this->mock(WatermarkService::class, function ($mock) use ($fakePdf): void {
        $mock->shouldReceive('generateWatermarkedPdf')->once()->andReturn($fakePdf);
    });

    $this->actingAs($recipient);
    $response = $this->get(route('documents.download', $document->id));
    $response->assertDownload('2026-FWD-DL-TEST.pdf');

    @unlink($fakePdf);
});

it('rejects forwarding when the actor lacks document.forward permission', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $document = fwdMakeDocument($sender);

    $this->actingAs($sender);

    $this->postJson(route('documents.forward.store', $document->id), [
        'recipient_id' => $recipient->id,
    ])->assertForbidden();

    expect(DB::table('document_user_targets')
        ->where('document_id', $document->id)
        ->where('user_id', $recipient->id)
        ->exists())->toBeFalse();
});

it('rejects forwarding a document the actor cannot view', function (): void {
    $uploader = User::factory()->create();
    $document = fwdMakeDocument($uploader, [
        'target_audience' => 'specific_users',
        'uploaded_by' => $uploader->id,
    ]);

    $sender = User::factory()->create([
        'institution_id' => Institution::query()->where('code', 'MESRS')->value('id'),
    ]);
    fwdGrantPermission($sender, 'document.forward');

    $recipient = User::factory()->create();

    $this->actingAs($sender);

    $this->postJson(route('documents.forward.store', $document->id), [
        'recipient_id' => $recipient->id,
    ])->assertForbidden();

    expect(DB::table('document_user_targets')
        ->where('document_id', $document->id)
        ->where('user_id', $recipient->id)
        ->exists())->toBeFalse();
});

it('rejects forwarding to an inactive recipient', function (): void {
    $sender = User::factory()->create();
    fwdGrantPermission($sender, 'document.forward');

    $recipient = User::factory()->create(['is_active' => false]);
    $document = fwdMakeDocument($sender);

    $this->actingAs($sender);

    $response = $this->postJson(route('documents.forward.store', $document->id), [
        'recipient_id' => $recipient->id,
    ]);

    $response->assertStatus(422);
    expect($response->json('message'))->toContain('désactivé');

    expect(DB::table('document_user_targets')
        ->where('document_id', $document->id)
        ->where('user_id', $recipient->id)
        ->exists())->toBeFalse();
});

it('rejects forwarding to oneself', function (): void {
    $sender = User::factory()->create();
    fwdGrantPermission($sender, 'document.forward');
    $document = fwdMakeDocument($sender);

    $this->actingAs($sender);

    $this->postJson(route('documents.forward.store', $document->id), [
        'recipient_id' => $sender->id,
    ])->assertStatus(422);
});

it('rejects forwarding non-active documents', function (): void {
    $sender = User::factory()->create();
    fwdGrantPermission($sender, 'document.forward');
    $recipient = User::factory()->create();

    $draft = fwdMakeDocument($sender, ['status' => 'draft']);
    $archived = fwdMakeDocument($sender, ['status' => 'archived']);

    $this->actingAs($sender);

    $this->postJson(route('documents.forward.store', $draft->id), [
        'recipient_id' => $recipient->id,
    ])->assertStatus(422);

    $this->postJson(route('documents.forward.store', $archived->id), [
        'recipient_id' => $recipient->id,
    ])->assertStatus(422);
});

it('does not create a duplicate row when forwarding the same document twice', function (): void {
    $sender = User::factory()->create();
    fwdGrantPermission($sender, 'document.forward');
    $recipient = User::factory()->create();
    $document = fwdMakeDocument($sender);

    $action = app(ForwardDocumentToUserAction::class);

    $first = $action->execute($sender, $document, $recipient->id);
    $second = $action->execute($sender, $document, $recipient->id);

    expect($first['was_already_targeted'])->toBeFalse();
    expect($second['was_already_targeted'])->toBeTrue();

    $count = DB::table('document_user_targets')
        ->where('document_id', $document->id)
        ->where('user_id', $recipient->id)
        ->count();
    expect($count)->toBe(1);

    // Each forward still produces a notification + audit entry.
    expect(Notification::query()
        ->where('recipient_user_id', $recipient->id)
        ->where('type', 'document.forwarded')
        ->count())->toBe(2);
    expect(AuditLog::query()
        ->where('event_type', 'document.forwarded')
        ->where('resource_id', $document->id)
        ->count())->toBe(2);
});

it('rejects forwarding to an unknown recipient id', function (): void {
    $sender = User::factory()->create();
    fwdGrantPermission($sender, 'document.forward');
    $document = fwdMakeDocument($sender);

    $this->actingAs($sender);

    $this->postJson(route('documents.forward.store', $document->id), [
        'recipient_id' => 999_999,
    ])->assertStatus(422); // FormRequest exists rule -> 422 with errors
});

it('exposes the forward action only to users with the permission on the list page', function (): void {
    $allowed = User::factory()->create();
    fwdGrantPermission($allowed, 'document.forward');
    fwdMakeDocument($allowed, ['title' => 'Visible doc actif']);

    $this->actingAs($allowed);
    $response = $this->get(route('documents.index'));
    $response->assertOk()->assertSee('open-forward-modal', false);

    $other = User::factory()->create();
    fwdMakeDocument($other, ['title' => 'Visible doc actif']);

    $this->actingAs($other);
    $response = $this->get(route('documents.index'));
    $response->assertOk()->assertDontSee('open-forward-modal', false);
});

it('requires the forward permission to query the user search endpoint', function (): void {
    $user = User::factory()->create();
    $this->actingAs($user);

    $this->getJson(route('documents.forward.search-users'))->assertForbidden();

    fwdGrantPermission($user, 'document.forward');
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    $this->getJson(route('documents.forward.search-users'))->assertOk();
});

it('only returns active users from the search endpoint, excluding the current user', function (): void {
    $actor = User::factory()->create(['full_name' => 'Actor Only']);
    fwdGrantPermission($actor, 'document.forward');

    $active = User::factory()->create(['full_name' => 'Alice Active', 'email' => 'alice@example.com']);
    $inactive = User::factory()->create(['full_name' => 'Bob Inactive', 'is_active' => false]);

    $this->actingAs($actor);
    $response = $this->getJson(route('documents.forward.search-users'));
    $response->assertOk();

    $ids = collect($response->json('data'))->pluck('id')->all();
    expect($ids)->toContain($active->id);
    expect($ids)->not->toContain($inactive->id);
    expect($ids)->not->toContain($actor->id);
});

it('throws a typed exception when the action is called with permission denied', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();
    $document = fwdMakeDocument($sender);

    $action = app(ForwardDocumentToUserAction::class);

    expect(fn () => $action->execute($sender, $document, $recipient->id))
        ->toThrow(DocumentForwardException::class);
});

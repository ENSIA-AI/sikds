<?php

declare(strict_types=1);

use App\Domain\Documents\Models\Document;
use App\Domain\Users\Models\Institution;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    $this->withoutVite();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

/**
 * @param  array<string, mixed>  $overrides
 */
function visMakeDocument(User $uploader, array $overrides = []): Document
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

it('does not grant access from a stray user target when the audience is not specific_users', function (): void {
    $uploader = User::factory()->create();
    // Outsider: no view.all permission, no role, different institution → only a
    // direct user target could unlock the document.
    $outsider = User::factory()->create();
    $document = visMakeDocument($uploader, ['target_audience' => 'specific_roles']);

    // Stray pivot row with NO assigned_by (i.e. not a forward grant).
    DB::table('document_user_targets')->insert([
        'document_id' => $document->id,
        'user_id' => $outsider->id,
        'created_at' => now(),
    ]);

    expect($document->refresh()->isAccessibleBy($outsider))->toBeFalse();
});

it('grants access from a user target when the audience is specific_users', function (): void {
    $uploader = User::factory()->create();
    $targeted = User::factory()->create();
    $document = visMakeDocument($uploader, ['target_audience' => 'specific_users']);

    DB::table('document_user_targets')->insert([
        'document_id' => $document->id,
        'user_id' => $targeted->id,
        'created_at' => now(),
    ]);

    expect($document->refresh()->isAccessibleBy($targeted))->toBeTrue();
});

it('grants access from a forward grant (assigned_by set) regardless of audience', function (): void {
    $uploader = User::factory()->create();
    $recipient = User::factory()->create();
    $document = visMakeDocument($uploader, ['target_audience' => 'specific_roles']);

    // Forward grants set assigned_by and are intentionally audience-independent.
    DB::table('document_user_targets')->insert([
        'document_id' => $document->id,
        'user_id' => $recipient->id,
        'assigned_by' => $uploader->id,
        'created_at' => now(),
    ]);

    expect($document->refresh()->isAccessibleBy($recipient))->toBeTrue();
});

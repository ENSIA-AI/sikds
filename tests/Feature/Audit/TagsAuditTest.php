<?php

declare(strict_types=1);

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Tags\Models\Tag;
use App\Domain\Users\Models\Permission;
use App\Domain\Users\Models\Role;
use App\Domain\Users\Models\User;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Spatie\Permission\PermissionRegistrar;

use function Pest\Laravel\actingAs;

beforeEach(function (): void {
    $this->withoutMiddleware(PreventRequestForgery::class);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
});

function makeUserWithPermissions(array $codes): User
{
    $role = Role::factory()->create([
        'name' => 'Tag Tester '.fake()->unique()->word(),
    ]);

    foreach ($codes as $code) {
        $permission = Permission::query()->firstOrCreate(
            ['code' => $code],
            [
                'name' => $code,
                'category' => 'tags',
                'guard_name' => 'web',
            ]
        );
        $role->permissions()->syncWithoutDetaching([$permission->id]);
    }

    $user = User::factory()->create();
    $user->roles()->attach($role->id);
    app(PermissionRegistrar::class)->forgetCachedPermissions();

    return $user;
}

it('writes a tag.created audit log with the actor and tag metadata', function (): void {
    $user = makeUserWithPermissions(['tag.manage']);

    actingAs($user);

    $response = $this->post(route('tags.store'), [
        'name' => 'Conformité',
        'color' => '#aabbcc',
        'category_mode' => 'none',
    ]);

    $response->assertRedirect(route('tags.index'));

    $tag = Tag::query()->where('name', 'Conformité')->firstOrFail();

    $log = AuditLog::query()
        ->where('event_type', 'tag.created')
        ->where('resource_id', $tag->id)
        ->latest('created_at')
        ->firstOrFail();

    expect($log->user_id)->toBe($user->id)
        ->and($log->user_email)->toBe($user->email)
        ->and($log->result)->toBe('success')
        ->and($log->resource_type)->toBe('tag')
        ->and($log->metadata)->toMatchArray([
            'tag_name' => 'Conformité',
            'color'    => '#aabbcc',
        ]);
});

it('blocks tag CRUD without tag.manage permission', function (): void {
    $user = User::factory()->create();
    actingAs($user);

    $response = $this->post(route('tags.store'), [
        'name' => 'Sans Permission',
        'color' => '#112233',
        'category_mode' => 'none',
    ]);

    $response->assertForbidden();
    expect(Tag::query()->where('name', 'Sans Permission')->exists())->toBeFalse();
});

it('refuses to delete a tag that is in use and writes a failed audit log', function (): void {
    $user = makeUserWithPermissions(['tag.manage']);

    $tag = Tag::query()->create([
        'name' => 'Utilisé',
        'slug' => 'utilise',
        'color' => '#fedcba',
        'is_predefined' => false,
        'created_by' => $user->id,
    ]);

    // Insert one document that uses the tag without going through full Document factory.
    $documentId = \Illuminate\Support\Facades\DB::table('documents')->insertGetId([
        'reference_number' => '2026-9999',
        'title' => 'Doc utilise tag',
        'description' => null,
        'file_path' => 'documents/x.pdf',
        'file_hash' => str_repeat('c', 64),
        'file_size' => 1024,
        'issue_date' => now()->toDateString(),
        'status' => 'draft',
        'indexing_status' => 'pending',
        'target_audience' => 'all',
        'version_number' => 1,
        'uploaded_by' => $user->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    \Illuminate\Support\Facades\DB::table('document_tags')->insert([
        'document_id' => $documentId,
        'tag_id'      => $tag->id,
        'assigned_at' => now(),
        'assigned_by' => $user->id,
    ]);

    actingAs($user);

    $response = $this->delete(route('tags.destroy', $tag));

    $response->assertRedirect(route('tags.index'));
    expect(Tag::query()->find($tag->id))->not->toBeNull();

    $log = AuditLog::query()
        ->where('event_type', 'tag.deleted')
        ->where('resource_id', $tag->id)
        ->latest('created_at')
        ->firstOrFail();

    expect($log->result)->toBe('failed')
        ->and($log->metadata['reason'] ?? null)->toBe('tag_in_use')
        ->and((int) ($log->metadata['usage_count'] ?? 0))->toBe(1);
});

it('logs tag.updated with before/after snapshots', function (): void {
    $user = makeUserWithPermissions(['tag.manage']);

    $tag = Tag::query()->create([
        'name' => 'Avant',
        'slug' => 'avant',
        'color' => '#001122',
        'is_predefined' => false,
        'created_by' => $user->id,
    ]);

    actingAs($user);

    $this->patch(route('tags.update', $tag), [
        'name' => 'Après',
        'color' => '#334455',
        'category_mode' => 'none',
    ])->assertRedirect(route('tags.index'));

    $log = AuditLog::query()
        ->where('event_type', 'tag.updated')
        ->where('resource_id', $tag->id)
        ->latest('created_at')
        ->firstOrFail();

    expect($log->metadata['before']['name'] ?? null)->toBe('Avant')
        ->and($log->metadata['after']['name'] ?? null)->toBe('Après')
        ->and($log->metadata['after']['color'] ?? null)->toBe('#334455');
});

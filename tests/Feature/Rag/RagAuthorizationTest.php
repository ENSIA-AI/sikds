<?php

use App\Domain\Documents\Models\Document;
use App\Domain\Institutions\Models\Institution;
use App\Domain\Users\Models\User;
use App\Models\Permission;
use App\Models\Role;
use App\Services\Rag\Contracts\EmbeddingServiceInterface;
use App\Services\Rag\Contracts\RerankerServiceInterface;
use App\Services\Rag\PromptGuard;
use App\Services\Rag\RagQueryService;
use App\Services\Rag\TokenEstimator;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function (): void {
    config()->set('app.key', 'base64:'.base64_encode(random_bytes(32)));
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    config()->set('rag.authorization.enforce', true);
});

function ragGrantPermission(User $user, string $code): void
{
    Permission::query()->firstOrCreate(
        ['name' => $code, 'guard_name' => 'web'],
        ['code' => $code, 'description' => $code, 'category' => 'documents']
    );
    $user->givePermissionTo($code);
}

function ragCreateDocument(User $uploader, array $overrides = []): Document
{
    $defaults = [
        'reference_number' => now()->format('Y').'-'.str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT),
        'title' => 'RAG '.fake()->unique()->lexify('DOC???'),
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

function ragServiceForTests(): RagQueryService
{
    return new class(
        mock(EmbeddingServiceInterface::class),
        mock(RerankerServiceInterface::class),
        new PromptGuard(),
        new TokenEstimator(),
    ) extends RagQueryService {
        /**
         * @return array<int, int>
         */
        public function exposedAuthorizedIds(User $user): array
        {
            return $this->resolveAuthorizedDocumentIds($user);
        }
    };
}

test('rag authorization includes role and direct-user targeted active indexed documents', function () {
    $inst = Institution::query()->firstOrCreate(
        ['code' => 'INST-RAG'],
        ['name' => 'Institution RAG', 'type' => 'university', 'domain' => 'rag.test']
    );
    $uploader = User::factory()->create();
    $user = User::factory()->create(['institution_id' => $inst->id]);
    $role = Role::query()->firstOrCreate(
        ['name' => 'RAGRole', 'guard_name' => 'web'],
        ['slug' => 'rag-role', 'is_system_role' => false]
    );
    $otherRole = Role::query()->firstOrCreate(
        ['name' => 'OtherRAGRole', 'guard_name' => 'web'],
        ['slug' => 'other-rag-role', 'is_system_role' => false]
    );
    $user->assignRole($role);
    ragGrantPermission($user, 'rag.query');

    $docAll = ragCreateDocument($uploader, ['target_audience' => 'all']);
    $docRole = ragCreateDocument($uploader, ['target_audience' => 'specific_roles']);
    $docDirect = ragCreateDocument($uploader, ['target_audience' => 'specific_users']);
    $docOtherRole = ragCreateDocument($uploader, ['target_audience' => 'specific_roles']);
    $docNotIndexed = ragCreateDocument($uploader, ['target_audience' => 'all', 'indexing_status' => 'pending']);
    $docArchived = ragCreateDocument($uploader, ['target_audience' => 'all', 'status' => 'archived']);

    DB::table('document_role_targets')->insert([
        ['document_id' => $docRole->id, 'role_id' => $role->id, 'created_at' => now()],
        ['document_id' => $docOtherRole->id, 'role_id' => $otherRole->id, 'created_at' => now()],
    ]);
    DB::table('document_user_targets')->insert([
        'document_id' => $docDirect->id,
        'user_id' => $user->id,
        'created_at' => now(),
    ]);

    $authorized = ragServiceForTests()->exposedAuthorizedIds($user);

    expect($authorized)->toContain($docAll->id);
    expect($authorized)->toContain($docRole->id);
    expect($authorized)->toContain($docDirect->id);
    expect($authorized)->not->toContain($docOtherRole->id);
    expect($authorized)->not->toContain($docNotIndexed->id);
    expect($authorized)->not->toContain($docArchived->id);
});

test('rag authorized ids match visible active indexed documents', function () {
    $inst = Institution::query()->firstOrCreate(
        ['code' => 'INST-RAG2'],
        ['name' => 'Institution RAG 2', 'type' => 'university', 'domain' => 'rag2.test']
    );
    $uploader = User::factory()->create();
    $user = User::factory()->create(['institution_id' => $inst->id]);
    $role = Role::query()->firstOrCreate(
        ['name' => 'RAGRole2', 'guard_name' => 'web'],
        ['slug' => 'rag-role-2', 'is_system_role' => false]
    );
    $user->assignRole($role);

    $docInst = ragCreateDocument($uploader, ['target_audience' => 'specific_institutions']);
    $docRole = ragCreateDocument($uploader, ['target_audience' => 'specific_roles']);
    $docDirect = ragCreateDocument($uploader, ['target_audience' => 'specific_users']);
    $docDraft = ragCreateDocument($uploader, ['status' => 'draft', 'indexing_status' => 'failed']);

    DB::table('document_institution_targets')->insert([
        'document_id' => $docInst->id,
        'institution_id' => $inst->id,
        'created_at' => now(),
    ]);
    DB::table('document_role_targets')->insert([
        'document_id' => $docRole->id,
        'role_id' => $role->id,
        'created_at' => now(),
    ]);
    DB::table('document_user_targets')->insert([
        'document_id' => $docDirect->id,
        'user_id' => $user->id,
        'created_at' => now(),
    ]);

    $visibleActiveIndexed = Document::query()
        ->visibleTo($user)
        ->where('status', 'active')
        ->where('indexing_status', 'indexed')
        ->whereNull('deleted_at')
        ->pluck('id')
        ->map(fn ($id): int => (int) $id)
        ->all();

    $authorized = ragServiceForTests()->exposedAuthorizedIds($user);

    sort($visibleActiveIndexed);
    sort($authorized);

    expect($authorized)->toBe($visibleActiveIndexed);
    expect($authorized)->not->toContain($docDraft->id);
});

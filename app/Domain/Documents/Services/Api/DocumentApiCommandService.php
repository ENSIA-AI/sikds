<?php

declare(strict_types=1);

namespace App\Domain\Documents\Services\Api;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Audit\Services\AuditService;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DocumentVersion;
use App\Domain\Tags\Models\Tag;
use App\Domain\Users\Models\User;
use App\Http\Requests\Api\Documents\StoreDocumentsRequest;
use App\Http\Requests\Api\Documents\UpdateDocumentRequest;
use App\Jobs\IndexDocumentJob;
use App\Services\Notifications\DocumentNotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class DocumentApiCommandService
{
    public function __construct(
        private readonly DocumentApiAuthorizationService $authorization,
        private readonly DocumentNotificationService $notifications,
        private readonly AuditService $auditService,
    ) {}

    /**
     * @return array{message: string, documents: array<int, array<string, mixed>>}
     */
    public function store(StoreDocumentsRequest $request, User $user): array
    {
        $this->authorization->assertPermission($user, 'document.create');

        /** @var array<int, UploadedFile> $files */
        $files = $request->validated('files');
        /** @var array<int, array<string, mixed>> $metaList */
        $metaList = $request->validated('documents_meta', []);

        $created = [];
        foreach ($files as $idx => $file) {
            $meta = $metaList[$idx] ?? null;
            if (! is_array($meta)) {
                abort(Response::HTTP_UNPROCESSABLE_ENTITY, "Métadonnées invalides pour le fichier #".($idx + 1).'.');
            }

            $this->assertPdfHeader($file);
            $created[] = DB::transaction(function () use ($file, $meta, $user, $request): array {
                $reference = $this->generateReferenceNumber();
                $stored = $this->storePdf($file, $reference, 1);

                $document = Document::query()->create([
                    'reference_number' => $reference,
                    'title' => $meta['title'],
                    'description' => $meta['description'] ?? null,
                    'file_path' => $stored['path'],
                    'file_hash' => $stored['hash'],
                    'file_size' => $stored['size'],
                    'issue_date' => $meta['issue_date'],
                    'effective_date' => $meta['effective_date'] ?? null,
                    'expiration_date' => $meta['expiration_date'] ?? null,
                    'status' => 'draft',
                    'indexing_status' => 'pending',
                    'target_audience' => $meta['target_audience'],
                    'version_number' => 1,
                    'uploaded_by' => $user->id,
                ]);

                $this->syncTargets($document, $meta);
                $this->syncTags($document, $meta['tag_ids'] ?? [], $user, $request);

                $this->audit($request, $user, 'document.uploaded', 'document', $document->id, [
                    'reference_number' => $document->reference_number,
                    'status' => $document->status,
                    'target_audience' => $document->target_audience,
                    'file_size' => $document->file_size,
                ]);

                return [
                    'id' => $document->id,
                    'reference_number' => $document->reference_number,
                    'status' => $document->status,
                    'version_number' => $document->version_number,
                ];
            });
        }

        return [
            'message' => 'Document(s) créé(s) avec succès.',
            'documents' => $created,
        ];
    }

    public function update(UpdateDocumentRequest $request, int $id, User $user): Document
    {
        $this->authorization->assertPermission($user, 'document.edit');

        $document = Document::withTrashed()->findOrFail($id);

        // Ensure the user can only edit documents from their own institution
        // unless they have document.view.all (Super Admin / global editor).
        $this->authorization->assertInstitutionScope($user, $document);

        if ($document->status === 'soft_deleted') {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Impossible de modifier un document supprimé.');
        }

        /** @var array<string, mixed> $validated */
        $validated = $request->validated();

        if (isset($validated['file']) && $validated['file'] instanceof UploadedFile) {
            $this->assertPdfHeader($validated['file']);
        }

        return DB::transaction(function () use ($validated, $document, $request, $user): Document {
            $oldStatus = $document->status;
            $fileUpdated = isset($validated['file']) && $validated['file'] instanceof UploadedFile;
            $previousVersion = (int) $document->version_number;
            $before = [
                'title' => $document->title,
                'description' => $document->description,
                'issue_date' => optional($document->issue_date)->toDateString(),
                'effective_date' => optional($document->effective_date)->toDateString(),
                'expiration_date' => optional($document->expiration_date)->toDateString(),
                'target_audience' => $document->target_audience,
                'status' => $document->status,
                'version_number' => (int) $document->version_number,
            ];

            if ($fileUpdated) {
                $this->purgeDocumentChunks($document->id);
                DocumentVersion::query()->create([
                    'document_id' => $document->id,
                    'version_number' => $document->version_number,
                    'file_path' => $document->file_path,
                    'file_hash' => $document->file_hash,
                    'status' => 'archived',
                    'metadata' => [
                        'title' => $document->title,
                        'description' => $document->description,
                        'issue_date' => optional($document->issue_date)->toDateString(),
                        'effective_date' => optional($document->effective_date)->toDateString(),
                        'expiration_date' => optional($document->expiration_date)->toDateString(),
                        'target_audience' => $document->target_audience,
                    ],
                    'created_at' => now(),
                    'created_by' => $user->id,
                ]);

                $newVersion = $document->version_number + 1;
                $stored = $this->storePdf($validated['file'], $document->reference_number, $newVersion);
                $document->file_path = $stored['path'];
                $document->file_hash = $stored['hash'];
                $document->file_size = $stored['size'];
                $document->version_number = $newVersion;
                $document->indexing_status = $document->status === 'active' ? 'pending' : $document->indexing_status;
            }

            foreach (['title', 'description', 'issue_date', 'effective_date', 'expiration_date', 'target_audience'] as $field) {
                if (array_key_exists($field, $validated)) {
                    $document->{$field} = $validated[$field];
                }
            }
            $document->save();

            if (
                array_key_exists('target_audience', $validated)
                || array_key_exists('target_institution_ids', $validated)
                || array_key_exists('target_role_ids', $validated)
                || array_key_exists('target_user_ids', $validated)
            ) {
                $this->syncTargets($document, [
                    'target_audience' => $validated['target_audience'] ?? $document->target_audience,
                    'target_institution_ids' => $validated['target_institution_ids'] ?? [],
                    'target_role_ids' => $validated['target_role_ids'] ?? [],
                    'target_user_ids' => $validated['target_user_ids'] ?? [],
                ]);
            }

            if (array_key_exists('tag_ids', $validated)) {
                $this->syncTags($document, $validated['tag_ids'] ?? [], $user, $request);
            }

            $after = [
                'title' => $document->title,
                'description' => $document->description,
                'issue_date' => optional($document->issue_date)->toDateString(),
                'effective_date' => optional($document->effective_date)->toDateString(),
                'expiration_date' => optional($document->expiration_date)->toDateString(),
                'target_audience' => $document->target_audience,
                'status' => $document->status,
                'version_number' => (int) $document->version_number,
            ];

            $this->audit($request, $user, 'document.updated', 'document', $document->id, [
                'document_id' => $document->id,
                'reference_number' => $document->reference_number,
                'status_before' => $oldStatus,
                'status_after' => $document->status,
                'new_version' => $fileUpdated ? $document->version_number : null,
                'before' => $before,
                'after' => $after,
            ]);

            if ($document->status === 'active' && $fileUpdated) {
                IndexDocumentJob::dispatch($document->id)->onQueue('indexing');
            }

            //  notify recipients when a new version is published (update with file).
            if ($document->status === 'active' && $fileUpdated) {
                $changeSummary = isset($validated['change_summary']) && is_string($validated['change_summary'])
                    ? trim($validated['change_summary'])
                    : null;
                $this->notifications->notifyDocumentUpdated(
                    $document,
                    $document->version_number,
                    $previousVersion,
                    $changeSummary !== '' ? $changeSummary : null,
                );
            }

            return $document;
        });
    }

    public function publish(Request $request, int $id, User $user): Document
    {
        $this->authorization->assertPermission($user, 'document.publish');

        $document = Document::withTrashed()->findOrFail($id);
        $this->authorization->assertInstitutionScope($user, $document);

        if ($document->status !== 'draft') {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Seuls les documents brouillons peuvent être publiés.');
        }

        $document->status = 'active';
        $document->deleted_at = null;
        $document->indexing_status = 'pending';
        $document->save();

        IndexDocumentJob::dispatch($document->id)->onQueue('indexing');

        $this->audit($request, $user, 'document.published', 'document', $document->id, [
            'reference_number' => $document->reference_number,
            'status_before' => 'draft',
            'status_after' => 'active',
        ]);

        // notify all users in target audience when published.
        $this->notifications->notifyDocumentPublished($document);

        return $document;
    }

    public function archive(Request $request, int $id, User $user): Document
    {
        $this->authorization->assertPermission($user, 'document.publish');

        $document = Document::withTrashed()->findOrFail($id);
        $this->authorization->assertInstitutionScope($user, $document);

        if ($document->status !== 'active') {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Seuls les documents actifs peuvent être archivés.');
        }

        $document->status = 'archived';
        $document->indexing_status = 'failed';
        $document->save();
        $this->purgeDocumentChunks($document->id);

        $this->audit($request, $user, 'document.archived', 'document', $document->id, [
            'reference_number' => $document->reference_number,
            'status_before' => 'active',
            'status_after' => 'archived',
        ]);

        return $document;
    }

    public function softDelete(Request $request, int $id, User $user): void
    {
        $this->authorization->assertPermission($user, 'document.delete');

        $document = Document::withTrashed()->findOrFail($id);
        $this->authorization->assertInstitutionScope($user, $document);

        if ($document->status === 'soft_deleted') {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Le document est déjà supprimé.');
        }

        $previousStatus = $document->status;
        $document->status = 'soft_deleted';
        $document->indexing_status = 'failed';
        $document->save();
        $document->delete();
        $this->purgeDocumentChunks($document->id);

        $this->audit($request, $user, 'document.soft_deleted', 'document', $document->id, [
            'reference_number' => $document->reference_number,
            'previous_status' => $previousStatus,
            'status_after' => 'soft_deleted',
        ]);
    }

    public function restore(Request $request, int $id, User $user): Document
    {
        $this->authorization->assertCanRestore($user);

        $document = Document::withTrashed()->findOrFail($id);
        $this->authorization->assertInstitutionScope($user, $document);

        if ($document->status !== 'soft_deleted') {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Ce document n\'est pas en état supprimé.');
        }

        $previousStatus = $this->resolvePreviousStatusFromAudit($document->id);
        $restoredStatus = in_array($previousStatus, ['draft', 'active'], true) ? $previousStatus : 'draft';

        $document->restore();
        $document->status = $restoredStatus;
        if ($restoredStatus === 'active') {
            $document->indexing_status = 'pending';
        }
        $document->save();

        if ($restoredStatus === 'active') {
            $this->purgeDocumentChunks($document->id);
            IndexDocumentJob::dispatch($document->id)->onQueue('indexing');
        }

        $this->audit($request, $user, 'document.restored', 'document', $document->id, [
            'reference_number' => $document->reference_number,
            'restored_status' => $restoredStatus,
        ]);

        return $document;
    }

    private function assertPdfHeader(UploadedFile $file): void
    {
        $stream = fopen($file->getRealPath(), 'rb');
        if ($stream === false) {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Impossible de lire le fichier PDF.');
        }
        $header = (string) fread($stream, 5);
        fclose($stream);

        if ($header !== '%PDF-') {
            abort(Response::HTTP_UNPROCESSABLE_ENTITY, 'Fichier PDF invalide.');
        }
    }

    /**
     * @return array{path: string, hash: string, size: int}
     */
    private function storePdf(UploadedFile $file, string $reference, int $version): array
    {
        $hash = hash_file('sha256', $file->getRealPath());
        $safeName = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));
        $filename = $safeName !== '' ? "{$safeName}.pdf" : ('document-'.$version.'.pdf');
        $path = sprintf('documents/%s/%s/v%d/%s', Carbon::now()->format('Y'), $reference, $version, $filename);

        $disk = (string) config('filesystems.documents_disk', 'local');
        $written = Storage::disk($disk)->putFileAs(dirname($path), $file, basename($path));
        if ($written === false) {
            abort(Response::HTTP_INTERNAL_SERVER_ERROR, 'Échec de stockage du fichier ('.$disk.').');
        }

        return ['path' => $path, 'hash' => $hash, 'size' => (int) $file->getSize()];
    }

    private function generateReferenceNumber(): string
    {
        $year = now()->format('Y');
        $references = DB::table('documents')
            ->where('reference_number', 'like', $year.'-%')
            ->pluck('reference_number')
            ->all();

        $maxSequence = 0;
        foreach ($references as $reference) {
            if (! is_string($reference)) {
                continue;
            }
            $parts = explode('-', $reference, 2);
            if (count($parts) !== 2 || $parts[0] !== $year || ! ctype_digit($parts[1])) {
                continue;
            }
            $maxSequence = max($maxSequence, (int) $parts[1]);
        }

        return sprintf('%s-%04d', $year, $maxSequence + 1);
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function syncTargets(Document $document, array $meta): void
    {
        DB::table('document_institution_targets')->where('document_id', $document->id)->delete();
        DB::table('document_role_targets')->where('document_id', $document->id)->delete();
        DB::table('document_user_targets')->where('document_id', $document->id)->delete();

        $audience = (string) ($meta['target_audience'] ?? 'all');
        if ($audience === 'specific_institutions') {
            $rows = array_map(
                static fn (int $institutionId): array => [
                    'document_id' => $document->id,
                    'institution_id' => $institutionId,
                    'created_at' => now(),
                ],
                array_values(array_unique(array_map('intval', $meta['target_institution_ids'] ?? [])))
            );
            if ($rows !== []) {
                DB::table('document_institution_targets')->insert($rows);
            }
        } elseif ($audience === 'specific_roles') {
            $rows = array_map(
                static fn (int $roleId): array => [
                    'document_id' => $document->id,
                    'role_id' => $roleId,
                    'created_at' => now(),
                ],
                array_values(array_unique(array_map('intval', $meta['target_role_ids'] ?? [])))
            );
            if ($rows !== []) {
                DB::table('document_role_targets')->insert($rows);
            }
        }

        $directUserRows = array_map(
            static fn (int $userId): array => [
                'document_id' => $document->id,
                'user_id' => $userId,
                'created_at' => now(),
            ],
            array_values(array_unique(array_map('intval', $meta['target_user_ids'] ?? [])))
        );
        if ($directUserRows !== []) {
            DB::table('document_user_targets')->insert($directUserRows);
        }
    }

    /**
     * @param  array<int, int|string>  $tagIds
     */
    private function syncTags(Document $document, array $tagIds, User $user, Request $request): void
    {
        $existing = DB::table('document_tags')
            ->where('document_id', $document->id)
            ->pluck('tag_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $normalized = array_values(array_unique(array_map('intval', $tagIds)));

        $added   = array_values(array_diff($normalized, $existing));
        $removed = array_values(array_diff($existing, $normalized));

        if ($added === [] && $removed === []) {
            return;
        }

        $this->authorization->assertPermission($user, 'tag.assign');

        DB::table('document_tags')->where('document_id', $document->id)->delete();

        if ($normalized !== []) {
            $rows = array_map(
                static fn (int $tagId): array => [
                    'document_id' => $document->id,
                    'tag_id' => $tagId,
                    'assigned_at' => now(),
                    'assigned_by' => $user->id,
                ],
                $normalized
            );
            DB::table('document_tags')->insert($rows);
        }

        if ($added !== [] || $removed !== []) {
            $names = Tag::query()
                ->whereIn('id', array_unique([...$added, ...$removed]))
                ->pluck('name', 'id')
                ->all();

            foreach ($added as $tagId) {
                $this->auditService->record(
                    eventType: 'tag.assigned',
                    user: $user,
                    resourceType: 'document',
                    resourceId: $document->id,
                    metadata: [
                        'document_id'        => $document->id,
                        'document_title'     => $document->title,
                        'reference_number'   => $document->reference_number,
                        'tag_id'             => $tagId,
                        'tag_name'           => $names[$tagId] ?? null,
                    ],
                    request: $request,
                );
            }

            foreach ($removed as $tagId) {
                $this->auditService->record(
                    eventType: 'tag.removed',
                    user: $user,
                    resourceType: 'document',
                    resourceId: $document->id,
                    metadata: [
                        'document_id'        => $document->id,
                        'document_title'     => $document->title,
                        'reference_number'   => $document->reference_number,
                        'tag_id'             => $tagId,
                        'tag_name'           => $names[$tagId] ?? null,
                    ],
                    request: $request,
                );
            }
        }
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function audit(Request $request, User $user, string $event, string $resourceType, int $resourceId, array $metadata = []): void
    {
        $this->auditService->record(
            eventType: $event,
            user: $user,
            resourceType: $resourceType,
            resourceId: $resourceId,
            metadata: $metadata,
            request: $request,
        );
    }

    private function resolvePreviousStatusFromAudit(int $documentId): ?string
    {
        $meta = AuditLog::query()
            ->where('event_type', 'document.soft_deleted')
            ->where('resource_type', 'document')
            ->where('resource_id', $documentId)
            ->latest('created_at')
            ->value('metadata');

        if (! is_array($meta)) {
            return null;
        }

        $status = $meta['previous_status'] ?? null;

        return is_string($status) ? $status : null;
    }

    private function purgeDocumentChunks(int $documentId): void
    {
        DB::table('document_chunks')->where('document_id', $documentId)->delete();
    }
}


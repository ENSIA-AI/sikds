<?php

declare(strict_types=1);

namespace App\Http\Controllers\Common;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DocumentVersion;
use App\Domain\Documents\Models\DownloadLog;
use App\Domain\Institutions\Models\Institution;
use App\Domain\Users\Models\User;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DocumentsController
{
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
            'status' => array_values(array_filter((array) $request->query('status', []))),
            'tags' => array_values(array_filter((array) $request->query('tags', []))),
            'audience' => array_values(array_filter((array) $request->query('audience', []))),
        ];

        $query = $this->visibleDocumentsQuery($user);

        if ($filters['q'] !== '') {
            $q = $filters['q'];
            $query->where(function (Builder $sub) use ($q): void {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('reference_number', 'like', "%{$q}%");
            });
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('issue_date', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('issue_date', '<=', $filters['date_to']);
        }

        if ($filters['status'] !== []) {
            $query->whereIn('status', array_map(function (string $status): string {
                return $status === 'deleted' ? 'soft_deleted' : $status;
            }, $filters['status']));
        }

        if ($filters['tags'] !== []) {
            $tagNames = $filters['tags'];
            $query->whereExists(function ($sub) use ($tagNames): void {
                $sub->selectRaw('1')
                    ->from('document_tags')
                    ->join('tags', 'tags.id', '=', 'document_tags.tag_id')
                    ->whereColumn('document_tags.document_id', 'documents.id')
                    ->whereIn('tags.name', $tagNames);
            });
        }

        if ($filters['audience'] !== []) {
            $audiences = $this->mapAudienceLabelsToValues($filters['audience']);
            if ($audiences !== []) {
                $query->whereIn('target_audience', $audiences);
            }
        }

        $documents = $query
            ->orderByDesc('issue_date')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (Document $document): array => $this->mapListDocument($document, $user));

        return view('documents.index', [
            'activeNav' => 'documents',
            'documents' => $documents,
            'filters' => $filters,
            'availableTags' => $this->availableTags(),
            'canPreview' => $this->canPreview($user),
        ]);
    }

    public function create(): View
    {
        return view('documents.upload', [
            'activeNav' => 'documents',
            'availableTags' => $this->availableTags(),
            'institutions' => $this->availableInstitutions(),
            'roles' => $this->availableRoles(),
        ]);
    }

    public function show(string $document): View
    {
        /** @var User $user */
        $user = Auth::user();
        abort_if(! $this->canPreview($user), 403, 'Prévisualisation réservée au Super-Admin. Téléchargez le document pour consultation.');

        $resolved = $this->resolveDocument($document);

        return view('documents.show', [
            'activeNav' => 'documents',
            'document' => $this->mapDetailDocument($resolved),
            'canEdit' => $user->can('document.edit'),
            'canDelete' => $user->can('document.delete'),
            'canPublish' => $user->can('document.publish'),
            'canRestore' => $user->can('document.restore') && $user->hasRole('Super Administrateur'),
        ]);
    }

    public function edit(string $document): View
    {
        /** @var User $user */
        $user = Auth::user();
        abort_if(! $user->can('document.edit'), 403, 'Permission document.edit requise.');

        $resolved = $this->resolveDocument($document);

        return view('documents.edit', [
            'activeNav' => 'documents',
            'document' => $this->mapEditDocument($resolved),
            'availableTags' => $this->availableTags(),
            'institutions' => $this->availableInstitutions(),
            'roles' => $this->availableRoles(),
            'canPublish' => $user->can('document.publish'),
        ]);
    }

    private function resolveDocument(string $document): Document
    {
        $query = Document::query()->withTrashed();

        $resolved = ctype_digit($document)
            ? $query->findOrFail((int) $document)
            : $query->where('reference_number', $document)->firstOrFail();

        $resolved->loadMissing(['uploader', 'targetInstitutions', 'targetRoles']);

        return $resolved;
    }

    private function visibleDocumentsQuery(User $user): Builder
    {
        $query = Document::query()->withTrashed();

        if ($this->canPreview($user)) {
            return $query;
        }

        return $query->where(function (Builder $sub) use ($user): void {
            $sub->where('uploaded_by', $user->id)
                ->orWhere(function (Builder $s): void {
                $s->where('status', 'active')->where('target_audience', 'all');
            })->orWhere(function (Builder $s) use ($user): void {
                if ($user->institution_id === null) {
                    $s->whereRaw('1 = 0');

                    return;
                }

                $s->where('status', 'active')
                    ->where('target_audience', 'specific_institutions')
                    ->whereExists(function ($q) use ($user): void {
                        $q->selectRaw('1')
                            ->from('document_institution_targets')
                            ->whereColumn('document_institution_targets.document_id', 'documents.id')
                            ->where('document_institution_targets.institution_id', $user->institution_id);
                    });
            })->orWhere(function (Builder $s) use ($user): void {
                $s->where('status', 'active')
                    ->whereExists(function ($q) use ($user): void {
                        $q->selectRaw('1')
                            ->from('document_user_targets')
                            ->whereColumn('document_user_targets.document_id', 'documents.id')
                            ->where('document_user_targets.user_id', $user->id);
                    });
            });
        });
    }

    private function mapListDocument(Document $document, User $user): array
    {
        $tags = $this->documentTags($document->id);

        $actions = ['download', 'copy'];
        if ($this->canPreview($user)) {
            $actions[] = 'view';
        }
        if ($user->can('document.edit')) {
            $actions[] = 'edit';
        }
        if ($document->status === 'soft_deleted') {
            if ($user->can('document.restore') && $user->hasRole('Super Administrateur')) {
                $actions[] = 'restore';
            }
        } elseif ($user->can('document.delete')) {
            $actions[] = 'delete';
        }

        return [
            'id' => $document->id,
            'title' => $document->title,
            'reference' => $document->reference_number,
            'status' => $document->status === 'soft_deleted' ? 'deleted' : $document->status,
            'tags' => array_slice($tags, 0, 2),
            'extra_tags' => max(count($tags) - 2, 0),
            'target_audience' => $this->formatAudience($document),
            'issue_date' => $this->formatDate($document->issue_date),
            'actions' => $actions,
            'download_url' => route('documents.download', $document->id),
            'show_url' => route('documents.show', $document->id),
            'edit_url' => route('documents.edit', $document->id),
            'delete_url' => route('api.documents.destroy', $document->id),
            'restore_url' => route('api.documents.restore', $document->id),
        ];
    }

    private function mapDetailDocument(Document $document): array
    {
        $downloadHistory = DownloadLog::query()
            ->with('user')
            ->where('document_id', $document->id)
            ->orderByDesc('downloaded_at')
            ->get()
            ->map(function (DownloadLog $download, int $index): array {
                $name = $download->user?->full_name ?? $download->user?->name ?? 'Utilisateur inconnu';
                $email = $download->user?->email ?? 'Email indisponible';

                return [
                    'title' => 'Téléchargement #'.($index + 1),
                    'meta' => $name.' • '.$email.' • '.$download->downloaded_at?->format('d/m/Y H:i'),
                    'uuid' => $download->watermark_uuid,
                ];
            })
            ->values()
            ->all();

        $activities = AuditLog::query()
            ->where('resource_type', 'document')
            ->where('resource_id', $document->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(function (AuditLog $activity): array {
                $map = [
                    'document.download' => ['title' => 'Document téléchargé', 'icon' => 'fa-solid fa-download', 'class' => 'sikds-doc-event-icon--download'],
                    'document.published' => ['title' => 'Document publié', 'icon' => 'fa-regular fa-file-lines', 'class' => 'sikds-doc-event-icon--version'],
                    'document.updated' => ['title' => 'Document mis à jour', 'icon' => 'fa-regular fa-file-lines', 'class' => 'sikds-doc-event-icon--version'],
                    'document.uploaded' => ['title' => 'Document téléversé', 'icon' => 'fa-regular fa-file-lines', 'class' => 'sikds-doc-event-icon--version'],
                    'document.soft_deleted' => ['title' => 'Document supprimé', 'icon' => 'fa-regular fa-trash-can', 'class' => 'sikds-doc-event-icon--share'],
                    'document.restored' => ['title' => 'Document restauré', 'icon' => 'fa-solid fa-rotate-left', 'class' => 'sikds-doc-event-icon--share'],
                    'document.archived' => ['title' => 'Document archivé', 'icon' => 'fa-solid fa-box-archive', 'class' => 'sikds-doc-event-icon--share'],
                ];
                $data = $map[$activity->event_type] ?? ['title' => $activity->event_type, 'icon' => 'fa-regular fa-circle', 'class' => 'sikds-doc-event-icon--version'];

                return [
                    'title' => $data['title'],
                    'meta' => ($activity->user_email ?? 'Système').($activity->result ? ' • '.$activity->result : ''),
                    'timestamp' => $activity->created_at?->format('d/m/Y H:i') ?? '-',
                    'icon' => $data['icon'],
                    'icon_class' => $data['class'],
                ];
            })
            ->values()
            ->all();

        $archivedVersions = DocumentVersion::query()
            ->where('document_id', $document->id)
            ->orderByDesc('version_number')
            ->get()
            ->map(function (DocumentVersion $version): array {
                $metadata = is_array($version->metadata) ? $version->metadata : [];

                return [
                    'title' => 'Version '.$version->version_number,
                    'status' => null,
                    'status_class' => null,
                    'meta' => ($version->created_at?->format('d/m/Y') ?? '-') . ' • ' . $this->formatBytes((int) (DB::table('documents')->where('id', $version->document_id)->value('file_size') ?? 0)),
                    'description' => (string) ($metadata['description'] ?? 'Version archivée'),
                ];
            })
            ->values();

        $versions = collect([[
            'title' => 'Version '.$document->version_number,
            'status' => 'Actuelle',
            'status_class' => 'sikds-doc-pill--current',
            'meta' => $this->formatDate($document->updated_at).' • '.$this->formatBytes((int) $document->file_size),
            'description' => $document->description ?: 'Version courante du document.',
        ]])->concat($archivedVersions)->all();

        return [
            'id' => $document->id,
            'title' => $document->title,
            'reference' => $document->reference_number,
            'status' => $document->status === 'soft_deleted' ? 'deleted' : $document->status,
            'description' => $document->description ?: 'Aucune description fournie.',
            'tags' => $this->documentTags($document->id),
            'institution' => $document->uploader?->institution?->name ?? 'Non renseignée',
            'issue_date' => $this->formatDate($document->issue_date),
            'effective_date' => $this->formatDate($document->effective_date),
            'expiry_date' => $this->formatDate($document->expiration_date),
            'audience' => $this->formatAudience($document),
            'views' => 0,
            'downloads' => count($downloadHistory),
            'version' => 'v'.$document->version_number,
            'file_name' => basename((string) $document->file_path),
            'file_type' => 'PDF',
            'file_size' => $this->formatBytes((int) $document->file_size),
            'versions' => $versions,
            'download_history' => $downloadHistory,
            'activities' => $activities,
            'download_url' => route('documents.download', $document->id),
            'edit_url' => route('documents.edit', $document->id),
            'delete_url' => route('api.documents.destroy', $document->id),
            'restore_url' => route('api.documents.restore', $document->id),
            'publish_url' => route('api.documents.publish', $document->id),
            'archive_url' => route('api.documents.archive', $document->id),
        ];
    }

    private function mapEditDocument(Document $document): array
    {
        return [
            'id' => $document->id,
            'title' => $document->title,
            'reference' => $document->reference_number,
            'description' => $document->description,
            'audience' => $document->target_audience,
            'issue_date' => optional($document->issue_date)->format('Y-m-d'),
            'effective_date' => optional($document->effective_date)->format('Y-m-d'),
            'expiry_date' => optional($document->expiration_date)->format('Y-m-d'),
            'status' => $document->status,
            'status_label' => $this->statusLabel($document->status),
            'file_name' => basename((string) $document->file_path),
            'file_type' => 'PDF',
            'version' => 'v'.$document->version_number,
            'tags' => $this->documentTags($document->id),
            'tag_ids' => DB::table('document_tags')->where('document_id', $document->id)->pluck('tag_id')->map(fn ($id): int => (int) $id)->all(),
            'target_institution_ids' => $document->targetInstitutions()->pluck('institutions.id')->map(fn ($id): int => (int) $id)->all(),
            'target_role_ids' => $document->targetRoles()->pluck('roles.id')->map(fn ($id): int => (int) $id)->all(),
            'update_url' => route('api.documents.update', $document->id),
            'show_url' => route('documents.show', $document->id),
            'publish_url' => route('api.documents.publish', $document->id),
        ];
    }

    private function availableTags(): array
    {
        return DB::table('tags')
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn ($tag): array => [
                'id' => (int) $tag->id,
                'label' => (string) $tag->name,
                'class' => $this->tagClass((string) $tag->slug, (string) $tag->name),
            ])
            ->all();
    }

    private function availableInstitutions(): array
    {
        return Institution::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Institution $institution): array => [
                'id' => $institution->id,
                'name' => $institution->name,
            ])
            ->all();
    }

    private function availableRoles(): array
    {
        return Role::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
            ])
            ->all();
    }

    private function documentTags(int $documentId): array
    {
        return DB::table('document_tags')
            ->join('tags', 'tags.id', '=', 'document_tags.tag_id')
            ->where('document_tags.document_id', $documentId)
            ->orderBy('tags.name')
            ->get(['tags.id', 'tags.name', 'tags.slug'])
            ->map(fn ($tag): array => [
                'id' => (int) $tag->id,
                'label' => (string) $tag->name,
                'class' => $this->tagClass((string) $tag->slug, (string) $tag->name),
            ])
            ->all();
    }

    private function tagClass(string $slug, string $name): string
    {
        return match (strtolower($slug ?: $name)) {
            'directive' => 'sikds-tag--directive',
            'urgent' => 'sikds-tag--urgent',
            'decision' => 'sikds-tag--decision',
            'reglement' => 'sikds-tag--reg',
            'rapport' => 'sikds-tag--rapport',
            default => 'sikds-tag--directive',
        };
    }

    private function formatAudience(Document $document): string
    {
        return match ($document->target_audience) {
            'all' => 'Toutes les institutions',
            'specific_institutions' => $document->targetInstitutions->pluck('name')->filter()->join(', ') ?: 'Institutions spécifiques',
            'specific_roles' => $document->targetRoles->pluck('name')->filter()->join(', ') ?: 'Rôles spécifiques',
            default => 'Non renseigné',
        };
    }

    /**
     * @param  array<int, string>  $labels
     * @return array<int, string>
     */
    private function mapAudienceLabelsToValues(array $labels): array
    {
        return collect($labels)
            ->map(fn (string $label): ?string => match ($label) {
                'Toutes les institutions' => 'all',
                'Universités', 'Institutions spécifiques' => 'specific_institutions',
                'Cabinet du Ministre', 'Rôles spécifiques' => 'specific_roles',
                default => null,
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function canPreview(User $user): bool
    {
        return $user->can('document.view.all') && $user->hasRole('Super Administrateur');
    }

    private function formatDate($date): string
    {
        return $date?->format('d/m/Y') ?? '-';
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return number_format($value, $power === 0 ? 0 : 1, '.', ' ').' '.$units[$power];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'active' => 'Actif',
            'draft' => 'Brouillon',
            'archived' => 'Archivé',
            'soft_deleted' => 'Supprimé',
            default => ucfirst($status),
        };
    }
}

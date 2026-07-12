<?php

declare(strict_types=1);

namespace App\Http\Controllers\Common;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DocumentVersion;
use App\Domain\Documents\Models\DownloadLog;
use App\Domain\Documents\Services\Api\DocumentApiAuthorizationService;
use App\Domain\Institutions\Models\Institution;
use App\Domain\Users\Models\User;
use App\Domain\Users\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DocumentsController
{
    public function __construct(
        private readonly DocumentApiAuthorizationService $documentAuth,
    ) {}

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
            // Escape LIKE wildcards so user input matches literally (see Document::escapeLike).
            $needle = '%'.Document::escapeLike($filters['q']).'%';
            $query->where(function (Builder $sub) use ($needle): void {
                $sub->whereRaw("title LIKE ? ESCAPE '!'", [$needle])
                    ->orWhereRaw("reference_number LIKE ? ESCAPE '!'", [$needle]);
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
            // `uploader` is eager-loaded so isInstitutionScopedActionAllowed() in
            // mapListDocument() never lazy-loads it per row (N+1).
            ->with(['tags', 'targetInstitutions', 'targetRoles', 'targetUsers', 'uploader:id,institution_id'])
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
            'canForward' => $this->canForward($user),
        ]);
    }

    public function create(): View
    {
        return view('documents.upload', [
            'activeNav' => 'documents',
            'availableTags' => $this->availableTags(),
            'institutions' => $this->availableInstitutions(),
            'roles' => $this->availableRoles(),
            'targetUsers' => $this->availableTargetUsers(),
        ]);
    }

    public function show(string $document): View
    {
        /** @var User $user */
        $user = Auth::user();

        $resolved = $this->resolveDocument($document);

        $instScope = $this->documentAuth->isInstitutionScopedActionAllowed($user, $resolved);

        return view('documents.show', [
            'activeNav' => 'documents',
            'document' => $this->mapDetailDocument($resolved),
            'canEdit' => $user->can('document.edit') && $instScope,
            'canDelete' => $user->can('document.delete') && $instScope,
            'canPublish' => $user->can('document.publish') && $instScope,
            'canRestore' => $this->mayRestoreSoftDeletedDocument($user),
            'canForward' => $resolved->status === 'active'
                && ! $resolved->trashed()
                && $this->canForward($user)
                && $resolved->isAccessibleBy($user)
                && $instScope,
        ]);
    }

    public function edit(string $document): View
    {
        /** @var User $user */
        $user = Auth::user();

        $resolved = $this->resolveDocument($document);
        $this->documentAuth->assertInstitutionScope($user, $resolved);

        return view('documents.edit', [
            'activeNav' => 'documents',
            'document' => $this->mapEditDocument($resolved),
            'availableTags' => $this->availableTags(),
            'institutions' => $this->availableInstitutions(),
            'roles' => $this->availableRoles(),
            'targetUsers' => $this->availableTargetUsers(),
            'canPublish' => $user->can('document.publish')
                && $this->documentAuth->isInstitutionScopedActionAllowed($user, $resolved),
        ]);
    }

    private function resolveDocument(string $document): Document
    {
        $query = Document::query()->withTrashed();

        $resolved = ctype_digit($document)
            ? $query->findOrFail((int) $document)
            : $query->where('reference_number', $document)->firstOrFail();

        $resolved->loadMissing(['uploader', 'targetInstitutions', 'targetRoles', 'targetUsers']);

        return $resolved;
    }

    private function visibleDocumentsQuery(User $user): Builder
    {
        $query = Document::query()->withTrashed();

        if ($this->canPreview($user)) {
            return $query;
        }

        return $query->visibleTo($user);
    }

    private function mapListDocument(Document $document, User $user): array
    {
        $tags = $document->relationLoaded('tags')
            ? $document->tags->sortBy('name')->map(fn ($tag): array => [
                'id' => (int) $tag->id,
                'label' => (string) $tag->name,
                'style' => $this->resolveTagStyle($tag->color ?? null),
            ])->values()->all()
            : $this->documentTags($document->id);

        $uiStatus = $document->status === 'soft_deleted' ? 'deleted' : $document->status;

        $instScope = $this->documentAuth->isInstitutionScopedActionAllowed($user, $document);

        $actions = ['download'];
        if ($document->status === 'draft' && $user->can('document.publish') && $instScope) {
            $actions[] = 'publish';
        }
        if ($this->canPreview($user)) {
            $actions[] = 'view';
        }
        if ($user->can('document.edit') && $instScope) {
            $actions[] = 'edit';
        }
        if ($document->status === 'active' && ! $document->trashed() && $this->canForward($user) && $instScope) {
            $actions[] = 'forward';
        }
        if ($document->status === 'active' && $user->can('document.publish') && $instScope) {
            $actions[] = 'archive';
        }
        if ($document->status === 'soft_deleted') {
            if ($this->mayRestoreSoftDeletedDocument($user)) {
                $actions[] = 'restore';
            }
        } elseif ($user->can('document.delete') && $instScope) {
            $actions[] = 'delete';
        }

        return [
            'id' => $document->id,
            'title' => $document->title,
            'reference' => $document->reference_number,
            'status' => $uiStatus,
            'status_label' => $this->statusLabel($document->status),
            'status_badge_class' => match ($uiStatus) {
                'active' => 'sikds-status--active',
                'draft' => 'sikds-status--draft',
                'archived' => 'sikds-status--archived',
                'deleted' => 'sikds-status--deleted',
                default => 'sikds-status--draft',
            },
            'status_icon' => match ($uiStatus) {
                'active' => 'fa-regular fa-circle-check',
                'draft' => 'fa-solid fa-gear',
                'archived' => 'fa-solid fa-box-archive',
                'deleted' => 'fa-regular fa-circle-xmark',
                default => 'fa-solid fa-gear',
            },
            'tags' => array_slice($tags, 0, 2),
            'tags_full' => $tags,
            'extra_tags' => max(count($tags) - 2, 0),
            'target_audience' => $this->formatAudience($document),
            'issue_date' => $this->formatDate($document->issue_date),
            'description_excerpt' => $this->excerptDescription($document->description),
            'actions' => $actions,
            'download_url' => route('documents.download', $document->id),
            'show_url' => route('documents.show', $document->id),
            'edit_url' => route('documents.edit', $document->id),
            'delete_url' => route('api.documents.destroy', $document->id),
            'restore_url' => route('api.documents.restore', $document->id),
            'publish_url' => route('api.documents.publish', $document->id),
            'archive_url' => route('api.documents.archive', $document->id),
            'forward_url' => route('documents.forward.store', $document->id),
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
                $name = $download->user?->full_name ?? $download->user?->name ?? __('Utilisateur inconnu');
                $email = $download->user?->email ?? __('Email indisponible');

                return [
                    'title' => __('Téléchargement #:n', ['n' => $index + 1]),
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
                    'document.download' => ['title' => __('Document téléchargé'), 'icon' => 'fa-solid fa-download', 'class' => 'sikds-doc-event-icon--download'],
                    'document.published' => ['title' => __('Document publié'), 'icon' => 'fa-regular fa-file-lines', 'class' => 'sikds-doc-event-icon--version'],
                    'document.updated' => ['title' => __('Document mis à jour'), 'icon' => 'fa-regular fa-file-lines', 'class' => 'sikds-doc-event-icon--version'],
                    'document.uploaded' => ['title' => __('Document téléversé'), 'icon' => 'fa-regular fa-file-lines', 'class' => 'sikds-doc-event-icon--version'],
                    'document.soft_deleted' => ['title' => __('Document supprimé'), 'icon' => 'fa-regular fa-trash-can', 'class' => 'sikds-doc-event-icon--share'],
                    'document.restored' => ['title' => __('Document restauré'), 'icon' => 'fa-solid fa-rotate-left', 'class' => 'sikds-doc-event-icon--share'],
                    'document.archived' => ['title' => __('Document archivé'), 'icon' => 'fa-solid fa-box-archive', 'class' => 'sikds-doc-event-icon--share'],
                    'document.forwarded' => ['title' => __('Document transféré'), 'icon' => 'fa-solid fa-share-from-square', 'class' => 'sikds-doc-event-icon--share'],
                    'tag.assigned' => ['title' => __('Étiquette ajoutée'), 'icon' => 'fa-solid fa-tag', 'class' => 'sikds-doc-event-icon--version'],
                    'tag.removed' => ['title' => __('Étiquette retirée'), 'icon' => 'fa-solid fa-tag', 'class' => 'sikds-doc-event-icon--share'],
                    'DOCUMENT_INDEXING_STARTED' => ['title' => __('Indexation démarrée'), 'icon' => 'fa-solid fa-bolt', 'class' => 'sikds-doc-event-icon--version'],
                    'DOCUMENT_INDEXING_COMPLETED' => ['title' => __('Indexation terminée'), 'icon' => 'fa-solid fa-circle-check', 'class' => 'sikds-doc-event-icon--version'],
                    'DOCUMENT_INDEXING_FAILED' => ['title' => __("Échec d'indexation"), 'icon' => 'fa-solid fa-triangle-exclamation', 'class' => 'sikds-doc-event-icon--share'],
                ];
                $data = $map[$activity->event_type] ?? ['title' => $activity->event_type, 'icon' => 'fa-regular fa-circle', 'class' => 'sikds-doc-event-icon--version'];

                $resultLabels = ['success' => __('Succès'), 'failed' => __('Échec'), 'warning' => __('Avertissement')];
                $resultLabel = $activity->result ? ($resultLabels[$activity->result] ?? $activity->result) : null;

                return [
                    'title' => $data['title'],
                    'meta' => ($activity->user_email ?? __('Système')).($resultLabel ? ' • '.$resultLabel : ''),
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
                    'title' => __('Version :n', ['n' => $version->version_number]),
                    'status' => null,
                    'status_class' => null,
                    'meta' => ($version->created_at?->format('d/m/Y') ?? '-').' • '.$this->formatBytes((int) (DB::table('documents')->where('id', $version->document_id)->value('file_size') ?? 0)),
                    'description' => (string) ($metadata['description'] ?? __('Version archivée')),
                ];
            })
            ->values();

        $versions = collect([[
            'title' => __('Version :n', ['n' => $document->version_number]),
            'status' => __('Actuelle'),
            'status_class' => 'sikds-doc-pill--current',
            'meta' => $this->formatDate($document->updated_at).' • '.$this->formatBytes((int) $document->file_size),
            'description' => $document->description ?: __('Version courante du document.'),
        ]])->concat($archivedVersions)->all();

        return [
            'id' => $document->id,
            'title' => $document->title,
            'reference' => $document->reference_number,
            'status' => $document->status === 'soft_deleted' ? 'deleted' : $document->status,
            'description' => $document->description ?: __('Aucune description fournie.'),
            'tags' => $this->documentTags($document->id),
            'institution' => $document->uploader?->institution?->name ?? __('Non renseignée'),
            'issue_date' => $this->formatDate($document->issue_date),
            'effective_date' => $this->formatDate($document->effective_date),
            'expiry_date' => $this->formatDate($document->expiration_date),
            'audience' => $this->formatAudience($document),
            'downloads' => count($downloadHistory),
            'version' => 'v'.$document->version_number,
            'file_name' => basename((string) $document->file_path),
            'file_type' => __('PDF'),
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
            'forward_url' => route('documents.forward.store', $document->id),
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
            'file_type' => __('PDF'),
            'version' => 'v'.$document->version_number,
            'tags' => $this->documentTags($document->id),
            'tag_ids' => DB::table('document_tags')->where('document_id', $document->id)->pluck('tag_id')->map(fn ($id): int => (int) $id)->all(),
            'target_institution_ids' => $document->targetInstitutions()->pluck('institutions.id')->map(fn ($id): int => (int) $id)->all(),
            'target_role_ids' => $document->targetRoles()->pluck('roles.id')->map(fn ($id): int => (int) $id)->all(),
            'target_user_ids' => $document->targetUsers()->pluck('users.id')->map(fn ($id): int => (int) $id)->all(),
            'update_url' => route('api.documents.update', $document->id),
            'show_url' => route('documents.show', $document->id),
            'publish_url' => route('api.documents.publish', $document->id),
        ];
    }

    private function availableTags(): array
    {
        return DB::table('tags')
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'color'])
            ->map(fn ($tag): array => [
                'id' => (int) $tag->id,
                'label' => (string) $tag->name,
                'style' => $this->resolveTagStyle($tag->color ?? null),
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

    private function availableTargetUsers(): array
    {
        return User::query()
            ->active()
            ->orderBy('full_name')
            ->orderBy('username')
            ->orderBy('email')
            ->get(['id', 'full_name', 'username', 'email'])
            ->map(fn (User $user): array => [
                'id' => (int) $user->id,
                'name' => (string) ($user->full_name ?: $user->username ?: $user->email),
                'email' => (string) $user->email,
            ])
            ->all();
    }

    private function documentTags(int $documentId): array
    {
        return DB::table('document_tags')
            ->join('tags', 'tags.id', '=', 'document_tags.tag_id')
            ->where('document_tags.document_id', $documentId)
            ->orderBy('tags.name')
            ->get(['tags.id', 'tags.name', 'tags.slug', 'tags.color'])
            ->map(fn ($tag): array => [
                'id' => (int) $tag->id,
                'label' => (string) $tag->name,
                'style' => $this->resolveTagStyle($tag->color ?? null),
            ])
            ->all();
    }

    private function resolveTagStyle(?string $rawColor): string
    {
        $background = $this->normalizeHexColor($rawColor) ?? '#e5e7eb';
        $textColor = $this->isLightColor($background) ? '#1f2937' : '#ffffff';

        return "background-color: {$background}; color: {$textColor};";
    }

    private function normalizeHexColor(?string $rawColor): ?string
    {
        if (! is_string($rawColor)) {
            return null;
        }

        $color = trim($rawColor);
        if ($color === '') {
            return null;
        }

        if (preg_match('/^#([0-9a-fA-F]{3})$/', $color, $matches) === 1) {
            $short = strtolower($matches[1]);

            return sprintf('#%s%s%s%s%s%s', $short[0], $short[0], $short[1], $short[1], $short[2], $short[2]);
        }

        if (preg_match('/^#([0-9a-fA-F]{6})$/', $color, $matches) === 1) {
            return '#'.strtolower($matches[1]);
        }

        return null;
    }

    private function isLightColor(string $hexColor): bool
    {
        $red = hexdec(substr($hexColor, 1, 2));
        $green = hexdec(substr($hexColor, 3, 2));
        $blue = hexdec(substr($hexColor, 5, 2));

        $luminance = (0.2126 * $red + 0.7152 * $green + 0.0722 * $blue) / 255;

        return $luminance > 0.6;
    }

    private function formatAudience(Document $document): string
    {
        return match ($document->target_audience) {
            'all' => __('Toutes les institutions'),
            'specific_institutions' => $document->targetInstitutions->pluck('name')->filter()->join(', ') ?: __('Institutions spécifiques'),
            'specific_roles' => $document->targetRoles->pluck('name')->filter()->join(', ') ?: __('Rôles spécifiques'),
            'specific_users' => $document->targetUsers->map(
                fn (User $user): string => (string) ($user->full_name ?: $user->username ?: $user->email)
            )->filter()->join(', ') ?: __('Utilisateurs spécifiques'),
            default => __('Non renseigné'),
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
                'Utilisateurs spécifiques' => 'specific_users',
                default => null,
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function canPreview(User $user): bool
    {
        return $user->can('document.view.all');
    }

    private function canForward(User $user): bool
    {
        return $user->can('document.forward');
    }

    /**
     * SRS §3.1 / §7.2: only the system Super Administrateur may restore soft-deleted documents.
     */
    private function mayRestoreSoftDeletedDocument(User $user): bool
    {
        return $user->hasRole('Super Administrateur');
    }

    private function excerptDescription(?string $html): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        $plain = trim(preg_replace('/\s+/', ' ', strip_tags($html)));

        return $plain === '' ? '' : Str::limit($plain, 220, '…');
    }

    private function formatDate($date): string
    {
        return $date?->format('d/m/Y') ?? '-';
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes <= 0) {
            return __('0 B');
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $power = min((int) floor(log($bytes, 1024)), count($units) - 1);
        $value = $bytes / (1024 ** $power);

        return number_format($value, $power === 0 ? 0 : 1, '.', ' ').' '.$units[$power];
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'active' => __('Actif'),
            'draft' => __('Brouillon'),
            'archived' => __('Archivé'),
            'soft_deleted' => __('Supprimé'),
            default => ucfirst($status),
        };
    }
}

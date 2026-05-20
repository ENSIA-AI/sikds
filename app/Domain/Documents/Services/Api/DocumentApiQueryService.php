<?php

declare(strict_types=1);

namespace App\Domain\Documents\Services\Api;

use App\Domain\Documents\Models\Document;
use App\Domain\Documents\Models\DocumentVersion;
use App\Domain\Users\Models\User;
use App\Http\Requests\Api\Documents\ListDocumentsRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DocumentApiQueryService
{
    public function __construct(
        private readonly DocumentApiAuthorizationService $authorization,
    ) {}

    public function index(ListDocumentsRequest $request, User $user): LengthAwarePaginator
    {
        $this->authorization->assertCanList($user);

        $includeDeleted = filter_var($request->query('include_deleted', false), FILTER_VALIDATE_BOOL);
        $query = Document::query();
        if ($includeDeleted && $this->authorization->canUseViewAll($user)) {
            $query->withTrashed();
        }

        $this->applyVisibilityScope($query, $user);
        $this->applyFilters($query, $request);

        $sortBy = (string) $request->query('sort_by', 'issue_date');
        $sortDir = (string) $request->query('sort_dir', 'desc');

        return $query
            ->orderBy($sortBy, $sortDir)
            ->paginate((int) $request->integer('per_page', 15))
            ->withQueryString();
    }

    /**
     * @return array<string, mixed>
     */
    public function show(int $id, User $user): array
    {
        $this->authorization->assertCanPreview($user);

        $document = Document::withTrashed()->findOrFail($id);

        return [
            'document' => $document,
            'tags' => DB::table('document_tags')
                ->join('tags', 'tags.id', '=', 'document_tags.tag_id')
                ->where('document_tags.document_id', $document->id)
                ->select('tags.id', 'tags.name', 'tags.slug', 'tags.color', 'tags.category')
                ->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function versions(int $id, User $user): array
    {
        $this->authorization->assertCanPreview($user);

        $document = Document::withTrashed()->findOrFail($id);
        $versions = DocumentVersion::query()
            ->where('document_id', $document->id)
            ->orderByDesc('version_number')
            ->get();

        return [
            'document_id' => $document->id,
            'reference_number' => $document->reference_number,
            'current_version' => $document->version_number,
            'versions' => $versions,
        ];
    }

    private function applyFilters(Builder $query, ListDocumentsRequest $request): void
    {
        if ($q = trim((string) $request->query('q', ''))) {
            $query->where(function (Builder $sub) use ($q): void {
                $sub->where('title', 'like', "%{$q}%")
                    ->orWhere('reference_number', 'like', "%{$q}%");
            });
        }

        if ($status = trim((string) $request->query('status', ''))) {
            $query->where('status', $status);
        }

        if ($dateFrom = trim((string) $request->query('date_from', ''))) {
            $query->whereDate('issue_date', '>=', $dateFrom);
        }

        if ($dateTo = trim((string) $request->query('date_to', ''))) {
            $query->whereDate('issue_date', '<=', $dateTo);
        }

        if ($tagId = (int) $request->integer('tag_id', 0)) {
            $query->whereExists(function ($sub) use ($tagId): void {
                $sub->selectRaw('1')
                    ->from('document_tags')
                    ->whereColumn('document_tags.document_id', 'documents.id')
                    ->where('document_tags.tag_id', $tagId);
            });
        }
    }

    private function applyVisibilityScope(Builder $query, User $user): void
    {
        $query->visibleTo($user);
    }
}


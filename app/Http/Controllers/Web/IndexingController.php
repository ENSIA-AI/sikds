<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Documents\Models\Document;
use App\Http\Controllers\Controller;
use App\Jobs\IndexDocumentJob;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Shows indexing status per document and allows retrying failed indexing.
 */
class IndexingController extends Controller
{
    public function index(Request $request): View
    {
        /** @var \App\Domain\Users\Models\User $user */
        $user = Auth::user();
        abort_if(! $user->can('audit.view'), 403, 'Accès refusé. Permission audit.view requise.');

        $documents = Document::query()
            ->select([
                'documents.id',
                'documents.title',
                'documents.reference_number',
                'documents.indexing_status',
                'documents.updated_at',
            ])
            ->addSelect([
                'chunks_count' => DB::table('document_chunks')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('document_chunks.document_id', 'documents.id'),
            ])
            ->orderByDesc('documents.updated_at')
            ->paginate(20)
            ->withQueryString();

        return view('indexing.index', compact('documents'));
    }

    public function retry(Request $request, Document $document): RedirectResponse
    {
        /** @var \App\Domain\Users\Models\User $user */
        $user = Auth::user();
        abort_if(! $user->can('audit.view'), 403, 'Accès refusé. Permission audit.view requise.');

        if ($document->indexing_status !== 'failed') {
            return back()->with('error', 'Seuls les documents en échec peuvent être relancés.');
        }

        $document->indexing_status = 'pending';
        $document->save();

        IndexDocumentJob::dispatch($document->id)->onQueue('indexing');

        return back()->with('success', 'Indexation relancée.');
    }
}


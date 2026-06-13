<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Audit\Services\AuditService;
use App\Http\Controllers\Controller;
use App\Services\Rag\RagQueryService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

/**
 * Presents the RAG query UI and serves the async query endpoint.
 */
class RagController extends Controller
{
    public function index(): View
    {
        return view('rag.index');
    }

    public function query(Request $request, RagQueryService $rag, AuditService $audit): JsonResponse
    {
        /** @var \App\Domain\Users\Models\User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'question' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        $question = (string) $validated['question'];

        try {
            $result = $rag->query($question, (int) $user->id);

            $meta = $result['meta'] ?? [];
            $audit->record(
                eventType: 'rag.query',
                result: ($result['refused'] ?? false) ? 'warning' : 'success',
                user: $user,
                resourceType: 'rag',
                metadata: [
                    'question' => $question,
                    'refused' => (bool) ($result['refused'] ?? false),
                    'reason' => $meta['reason'] ?? null,
                    'retrieved_chunk_ids' => $meta['retrieved_chunk_ids'] ?? [],
                    'citation_count' => count($result['citations'] ?? []),
                    'prompt_tokens' => (int) ($meta['prompt_tokens'] ?? 0),
                    'completion_tokens' => (int) ($meta['completion_tokens'] ?? 0),
                ],
                request: $request,
            );

            // `meta` is audit-only — never expose retrieval internals to the client.
            return response()->json(Arr::except($result, 'meta'));
        } catch (ConnectionException $e) {
            report($e);

            return response()->json([
                'message' => __('Le service IA configuré est injoignable ou a expiré. Vérifiez la connexion réseau et l\'URL du service.'),
            ], 503);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => __('La requête RAG a échoué.'),
            ], 500);
        }
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Audit\Services\AuditService;
use App\Http\Controllers\Controller;
use App\Services\Rag\RagQueryService;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

        try {
            $result = $rag->query((string) $validated['question'], (int) $user->id);

            $this->auditQuery($audit, $request, (string) $validated['question'], $result);

            // Internal-only signals (`reason`, `injection_detected`) stay server-side.
            return response()->json([
                'answer' => $result['answer'],
                'citations' => $result['citations'],
                'refused' => $result['refused'],
            ]);
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

    /**
     * Record an audit-log entry for a RAG query. The raw question is never
     * stored — only its length and the pipeline's security/refusal verdict.
     *
     * @param  array{refused:bool, reason:?string, injection_detected:bool, citations:array<int, mixed>}  $result
     */
    private function auditQuery(AuditService $audit, Request $request, string $question, array $result): void
    {
        if (! config('rag.security.audit_queries', true)) {
            return;
        }

        $injection = (bool) ($result['injection_detected'] ?? false);
        $refused = (bool) ($result['refused'] ?? false);

        $audit->record(
            eventType: 'rag.query',
            result: $injection ? 'blocked' : ($refused ? 'refused' : 'success'),
            resourceType: 'rag',
            metadata: [
                'question_length' => mb_strlen($question),
                'refused' => $refused,
                'reason' => $result['reason'] ?? null,
                'injection_detected' => $injection,
                'citation_count' => count($result['citations'] ?? []),
            ],
            request: $request,
        );
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

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
        /** @var \App\Domain\Users\Models\User $user */
        $user = Auth::user();
        abort_if(! $user->can('rag.query'), 403, 'Accès refusé. Permission rag.query requise.');

        return view('rag.index');
    }

    public function query(Request $request, RagQueryService $rag): JsonResponse
    {
        /** @var \App\Domain\Users\Models\User $user */
        $user = Auth::user();
        abort_if(! $user->can('rag.query'), 403, 'Accès refusé. Permission rag.query requise.');

        $validated = $request->validate([
            'question' => ['required', 'string', 'min:3', 'max:500'],
        ]);

        try {
            $result = $rag->query((string) $validated['question'], (int) $user->id);

            return response()->json($result);
        } catch (ConnectionException $e) {
            report($e);

            return response()->json([
                'message' => 'Le service IA configuré est injoignable ou a expiré. Vérifiez la connexion réseau et l’URL du service.',
            ], 503);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'RAG query failed.',
            ], 500);
        }
    }
}

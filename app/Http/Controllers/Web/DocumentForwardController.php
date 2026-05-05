<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Documents\Actions\ForwardDocumentToUserAction;
use App\Domain\Documents\Exceptions\DocumentForwardException;
use App\Domain\Documents\Models\Document;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use App\Http\Requests\Documents\ForwardDocumentRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Handles forwarding (sharing) a document to another active user, plus the
 * recipient autocomplete used by the forward modal. Stays thin: business
 * logic and audit/notification side-effects live in
 * `ForwardDocumentToUserAction`.
 */
class DocumentForwardController extends Controller
{
    public function __construct(
        private readonly ForwardDocumentToUserAction $action,
    ) {}

    public function store(ForwardDocumentRequest $request, int $id): JsonResponse
    {
        /** @var User $actor */
        $actor = Auth::user();

        $document = Document::query()->findOrFail($id);

        try {
            $result = $this->action->execute(
                actor: $actor,
                document: $document,
                recipientId: (int) $request->validated('recipient_id'),
                request: $request,
            );
        } catch (DocumentForwardException $exception) {
            return response()->json(
                ['message' => $exception->getMessage()],
                $exception->statusCode,
            );
        }

        $message = $result['was_already_targeted']
            ? 'Le destinataire avait déjà accès à ce document. Une nouvelle notification lui a été envoyée.'
            : 'Document transféré avec succès.';

        return response()->json([
            'message' => $message,
            'was_already_targeted' => $result['was_already_targeted'],
            'notification_id' => $result['notification']->id,
        ]);
    }

    /**
     * Lightweight typeahead used by the forward modal to suggest active users
     * by full name, username, email, or institution name. Always returns at
     * most 10 entries, never exposes inactive accounts, and excludes the
     * current actor.
     */
    public function searchUsers(Request $request): JsonResponse
    {
        /** @var User $actor */
        $actor = Auth::user();

        abort_unless($actor->can('document.forward'), 403, 'Permission document.forward requise.');

        $term = trim((string) $request->query('q', ''));

        $query = User::query()
            ->active()
            ->whereKeyNot($actor->id)
            ->with('institution:id,name,code');

        if ($term !== '') {
            $driver = DB::getDriverName();
            $like = $driver === 'pgsql' ? 'ilike' : 'like';
            $needle = '%'.$term.'%';

            $query->where(function (Builder $sub) use ($like, $needle): void {
                $sub->where('full_name', $like, $needle)
                    ->orWhere('username', $like, $needle)
                    ->orWhere('email', $like, $needle)
                    ->orWhereHas('institution', function (Builder $iq) use ($like, $needle): void {
                        $iq->where('name', $like, $needle)
                            ->orWhere('code', $like, $needle);
                    });
            });
        }

        $users = $query
            ->orderBy('full_name')
            ->orderBy('username')
            ->limit(10)
            ->get(['id', 'full_name', 'username', 'email', 'institution_id'])
            ->map(static fn (User $user): array => [
                'id' => (int) $user->id,
                'full_name' => (string) ($user->full_name ?? ''),
                'username' => (string) ($user->username ?? ''),
                'email' => (string) ($user->email ?? ''),
                'institution' => $user->institution?->name,
                'label' => trim((string) ($user->full_name ?: $user->username ?: $user->email)),
            ])
            ->all();

        return response()->json([
            'data' => $users,
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Notifications\Models\Notification;
use App\Domain\Notifications\Services\UserNotificationService;
use App\Domain\Users\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationInboxController extends Controller
{
    public function __construct(private readonly UserNotificationService $notifications) {}

    /**
     * Per-user inbox page (full list).
     */
    public function index(): View
    {
        /** @var User $user */
        $user = Auth::user();

        $items = $this->notifications->paginateForUser($user, 20);
        $unread = $this->notifications->unreadCountForUser($user);

        return view('notifications.inbox', [
            'items' => $items,
            'unreadCount' => $unread,
            'service' => $this->notifications,
            'activeNav' => 'notifications-inbox',
        ]);
    }

    /**
     * JSON endpoint for the header bell dropdown.
     */
    public function latest(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $limit = (int) $request->integer('limit', 5);
        $limit = max(1, min($limit, 10));

        $items = $this->notifications->latestForUser($user, $limit);

        $canPreviewDocuments = $user->can('document.view.all');

        $payload = $items->map(function (Notification $notification) use ($canPreviewDocuments): array {
            $documentId = $notification->document_id;
            $url = $this->resolveDocumentUrl($documentId, $canPreviewDocuments);

            return [
                'id' => $notification->id,
                'type' => $notification->type,
                'message' => $this->notifications->buildMessage($notification),
                'short_label' => $this->notifications->shortLabel($notification->type),
                'document_id' => $documentId,
                'document_title' => $notification->document?->title,
                'created_at' => $notification->created_at?->toIso8601String(),
                'created_at_human' => $notification->created_at?->locale('fr')->diffForHumans(),
                'read_at' => $notification->read_at?->toIso8601String(),
                'read' => $notification->read_at !== null,
                'url' => $url,
                'mark_read_url' => route('notifications.read', ['notification' => $notification->id]),
            ];
        })->all();

        return response()->json([
            'data' => $payload,
            'unread_count' => $this->notifications->unreadCountForUser($user),
            'inbox_url' => route('notifications.inbox'),
        ]);
    }

    /**
     * Mark a notification as read and optionally redirect to the related document.
     */
    public function read(Request $request, Notification $notification): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        abort_unless($notification->recipient_user_id === $user->id, 403);

        $this->notifications->markAsRead($notification);

        if ($request->expectsJson()) {
            return response()->json([
                'ok' => true,
                'unread_count' => $this->notifications->unreadCountForUser($user),
            ]);
        }

        if ($notification->document_id) {
            $target = $this->resolveDocumentUrl($notification->document_id, $user->can('document.view.all'));

            return redirect()->to($target);
        }

        return redirect()->route('notifications.inbox');
    }

    /**
     * Pick where to send a user when they click a notification linked to a document.
     * Users without preview permission go to the list (where their visibility is enforced)
     * instead of /documents/{id}, which would 403 for them.
     */
    private function resolveDocumentUrl(?int $documentId, bool $canPreview): string
    {
        if ($documentId !== null && $canPreview) {
            return route('documents.show', ['document' => $documentId]);
        }

        return route('documents.index');
    }

    public function markAllRead(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $count = $this->notifications->markAllAsReadForUser($user);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'updated' => $count, 'unread_count' => 0]);
        }

        return back()->with('status', 'Toutes les notifications ont été marquées comme lues.');
    }
}

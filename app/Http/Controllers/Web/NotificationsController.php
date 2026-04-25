<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class NotificationsController extends Controller
{
    public function index(Request $request): View
    {
        /** @var \App\Domain\Users\Models\User $user */
        $user = Auth::user();
        abort_if(! $user->can('audit.view'), 403, 'Accès refusé. Permission audit.view requise.');

        $query = DB::table('notifications as n')
            ->leftJoin('users as u', 'u.id', '=', 'n.recipient_user_id')
            ->leftJoin('documents as d', 'd.id', '=', 'n.document_id')
            ->select([
                'n.id',
                'n.type',
                'n.email_status',
                'n.email_sent_at',
                'n.email_error',
                'n.metadata',
                'n.created_at',
                'u.full_name as recipient_name',
                'u.email as recipient_email',
                'd.title as document_title',
                'd.reference_number as document_reference',
            ]);

        if ($q = trim((string) $request->input('q', ''))) {
            $query->where(function ($sub) use ($q): void {
                $sub->where('u.full_name', 'ilike', "%{$q}%")
                    ->orWhere('u.email', 'ilike', "%{$q}%")
                    ->orWhere('d.title', 'ilike', "%{$q}%")
                    ->orWhere('d.reference_number', 'ilike', "%{$q}%")
                    ->orWhere('n.type', 'ilike', "%{$q}%");
            });
        }

        if ($type = $request->input('type')) {
            $query->where('n.type', $type);
        }

        if ($status = $request->input('status')) {
            $query->where('n.email_status', $status);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->where('n.created_at', '>=', $dateFrom . ' 00:00:00');
        }

        if ($dateTo = $request->input('date_to')) {
            $query->where('n.created_at', '<=', $dateTo . ' 23:59:59');
        }

        $notifications = $query
            ->orderByDesc('n.created_at')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => DB::table('notifications')->count(),
            'sent' => DB::table('notifications')->where('email_status', 'sent')->count(),
            'failed' => DB::table('notifications')->where('email_status', 'failed')->count(),
            'pending' => DB::table('notifications')
                ->where(function ($sub): void {
                    $sub->whereNull('email_status')
                        ->orWhere('email_status', 'pending');
                })
                ->count(),
        ];

        $types = DB::table('notifications')
            ->select('type')
            ->distinct()
            ->orderBy('type')
            ->pluck('type');

        return view('notifications.index', compact('notifications', 'stats', 'types') + ['activeNav' => 'notifications']);
    }
}

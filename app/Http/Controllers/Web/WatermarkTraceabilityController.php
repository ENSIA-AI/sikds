<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Documents\Models\DownloadLog;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WatermarkTraceabilityController extends Controller
{
    /**
     * Display the traceability list with stats and filters.
     * Accepts GET params: q, date_from, date_to, document, user, institution
     */
    public function index(Request $request)
    {
        $query = DownloadLog::with(['user.institution', 'document'])
            ->orderBy('downloaded_at', 'desc');

        // Full-text search (UUID, document title/reference, user name/email)
        if ($q = $request->input('q')) {
            $query->where(function ($q2) use ($q) {
                $q2->where('watermark_uuid', 'ilike', "%{$q}%")
                    ->orWhereHas('document', fn ($d) => $d
                        ->where('title', 'ilike', "%{$q}%")
                        ->orWhere('reference_number', 'ilike', "%{$q}%"))
                    ->orWhereHas('user', fn ($u) => $u
                        ->where('full_name', 'ilike', "%{$q}%")
                        ->orWhere('email', 'ilike', "%{$q}%"));
            });
        }

        // Document title filter
        if ($document = $request->input('document')) {
            $query->whereHas('document', fn ($d) => $d
                ->where('title', 'ilike', "%{$document}%")
                ->orWhere('reference_number', 'ilike', "%{$document}%"));
        }

        // User name/email filter
        if ($userFilter = $request->input('user')) {
            $query->whereHas('user', fn ($u) => $u
                ->where('full_name', 'ilike', "%{$userFilter}%")
                ->orWhere('email', 'ilike', "%{$userFilter}%"));
        }

        // Institution filter
        if ($institution = $request->input('institution')) {
            $query->whereHas('user.institution', fn ($i) => $i
                ->where('name', 'ilike', "%{$institution}%"));
        }

        // Date range filter
        if ($dateFrom = $request->input('date_from')) {
            $query->where('downloaded_at', '>=', $dateFrom . ' 00:00:00');
        }
        if ($dateTo = $request->input('date_to')) {
            $query->where('downloaded_at', '<=', $dateTo . ' 23:59:59');
        }

        $logs = $query->paginate(15)->withQueryString();

        // Aggregate stats (always from the full table, no filters)
        $stats = [
            'total'        => DownloadLog::count(),
            'today'        => DownloadLog::whereDate('downloaded_at', today())->count(),
            'unique_users' => DownloadLog::distinct('user_id')->count('user_id'),
            'institutions' => DownloadLog::join('users', 'download_logs.user_id', '=', 'users.id')
                ->whereNotNull('users.institution_id')
                ->distinct('users.institution_id')
                ->count('users.institution_id'),
        ];

        return view('watermark.index', compact('logs', 'stats') + ['activeNav' => 'traceability']);
    }

    /**
     * Show detailed traceability record for a single watermark UUID.
     */
    public function show(string $uuid)
    {
        $log = DownloadLog::with(['user.institution', 'document'])
            ->where('watermark_uuid', $uuid)
            ->first();

        if (! $log) {
            return redirect()->route('watermark.index')
                ->withErrors(['uuid' => 'Aucun enregistrement trouvé pour l\'UUID : ' . $uuid]);
        }

        // Find related audit entry
        $auditEntry = AuditLog::where('resource_type', 'download_log')
            ->where('resource_id', $log->id)
            ->where('event_type', 'document.download')
            ->first();

        return view('watermark.show', compact('log', 'auditEntry') + ['activeNav' => 'traceability']);
    }
}


<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Domain\Audit\Models\AuditLog;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditController extends Controller
{
    public function index(Request $request): View
    {
        /** @var \App\Domain\Users\Models\User $user */
        $user = Auth::user();
        abort_if(! $user->can('audit.view'), 403, 'Accès refusé. Permission audit.view requise.');

        $query = $this->buildFilteredQuery($request);

        $logs = $query
            ->with('user:id,full_name,email')
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        $eventTypes = AuditLog::query()
            ->select('event_type')
            ->distinct()
            ->orderBy('event_type')
            ->limit(500)
            ->pluck('event_type');

        $activeFiltersCount = collect($request->query())
            ->except('page')
            ->filter(function ($value): bool {
                if (is_array($value)) {
                    return collect($value)->filter(fn ($v) => $v !== null && $v !== '')->isNotEmpty();
                }

                return $value !== null && $value !== '';
            })
            ->count();

        return view('audits.index', compact('logs', 'eventTypes', 'activeFiltersCount') + ['activeNav' => 'audits']);
    }

    public function export(Request $request): StreamedResponse|\Illuminate\Http\Response
    {
        /** @var \App\Domain\Users\Models\User $user */
        $user = Auth::user();
        abort_if(! $user->can('audit.view'), 403, 'Accès refusé. Permission audit.view requise.');

        $rows = $this->buildFilteredQuery($request)
            ->with('user:id,full_name,email')
            ->orderByDesc('created_at')
            ->limit(2000)
            ->get();

        $format = strtolower((string) $request->input('format', 'csv'));
        $filenameBase = 'audits-' . now()->format('Ymd-His');

        if ($format === 'json') {
            $payload = $rows->map(function ($log): array {
                return [
                    'datetime' => optional($log->created_at)->format('Y-m-d H:i:s'),
                    'event_type' => $log->event_type,
                    'result' => $log->result,
                    'actor_name' => $log->user?->full_name,
                    'actor_email' => $log->user_email ?? $log->user?->email,
                    'resource_type' => $log->resource_type,
                    'resource_id' => $log->resource_id,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'metadata' => $log->metadata,
                ];
            })->values();

            return Response::make(
                $payload->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
                200,
                [
                    'Content-Type' => 'application/json; charset=UTF-8',
                    'Content-Disposition' => 'attachment; filename="' . $filenameBase . '.json"',
                ]
            );
        }

        $filename = $filenameBase . '.csv';

        return response()->streamDownload(function () use ($rows): void {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'datetime',
                'event_type',
                'result',
                'actor_name',
                'actor_email',
                'resource_type',
                'resource_id',
                'ip_address',
                'user_agent',
                'metadata',
            ]);

            foreach ($rows as $log) {
                fputcsv($handle, [
                    optional($log->created_at)->format('Y-m-d H:i:s'),
                    $log->event_type,
                    $log->result,
                    $log->user?->full_name,
                    $log->user_email ?? $log->user?->email,
                    $log->resource_type,
                    $log->resource_id,
                    $log->ip_address,
                    $log->user_agent,
                    json_encode($log->metadata, JSON_UNESCAPED_UNICODE),
                ]);
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function buildFilteredQuery(Request $request)
    {
        $query = AuditLog::query();

        $exportDateFrom = $request->input('export_date_from');
        $exportDateTo = $request->input('export_date_to');
        if ($exportDateFrom || $exportDateTo) {
            if ($exportDateFrom) {
                $query->where('created_at', '>=', $exportDateFrom . ' 00:00:00');
            }
            if ($exportDateTo) {
                $query->where('created_at', '<=', $exportDateTo . ' 23:59:59');
            }
        }

        if ($q = trim((string) $request->input('q', ''))) {
            $query->where(function ($sub) use ($q): void {
                $sub->where('event_type', 'ilike', "%{$q}%")
                    ->orWhere('user_email', 'ilike', "%{$q}%")
                    ->orWhere('resource_type', 'ilike', "%{$q}%")
                    ->orWhereRaw('CAST(resource_id AS TEXT) ILIKE ?', ["%{$q}%"])
                    ->orWhereRaw('CAST(metadata AS TEXT) ILIKE ?', ["%{$q}%"]);
            });
        }

        // Multi-select: accept either ?event_type=X (single) or ?event_type[]=X&event_type[]=Y (array).
        $eventType = $request->input('event_type');
        $eventTypes = is_array($eventType)
            ? array_values(array_filter(array_map('strval', $eventType), fn ($v): bool => $v !== ''))
            : (is_string($eventType) && trim($eventType) !== '' ? [$eventType] : []);
        if ($eventTypes !== []) {
            $query->whereIn('event_type', $eventTypes);
        }

        $result = $request->input('result');
        $results = is_array($result)
            ? array_values(array_filter(array_map('strval', $result), fn ($v): bool => $v !== ''))
            : (is_string($result) && trim($result) !== '' ? [$result] : []);
        if ($results !== []) {
            $query->whereIn('result', $results);
        }

        if ($actor = trim((string) $request->input('actor', ''))) {
            $query->where(function ($sub) use ($actor): void {
                $sub->where('user_email', 'ilike', "%{$actor}%")
                    ->orWhereHas('user', function ($userQuery) use ($actor): void {
                        $userQuery->where('full_name', 'ilike', "%{$actor}%")
                            ->orWhere('email', 'ilike', "%{$actor}%");
                    });
            });
        }

        if ($userFilter = trim((string) $request->input('user', ''))) {
            $query->where(function ($sub) use ($userFilter): void {
                $sub->where('user_email', 'ilike', "%{$userFilter}%")
                    ->orWhereHas('user', function ($userQuery) use ($userFilter): void {
                        $userQuery->where('full_name', 'ilike', "%{$userFilter}%")
                            ->orWhere('email', 'ilike', "%{$userFilter}%");
                    });
            });
        }

        if ($resourceType = $request->input('resource_type')) {
            $query->where('resource_type', $resourceType);
        }

        if ($resourceId = trim((string) $request->input('resource_id', ''))) {
            $query->whereRaw('CAST(resource_id AS TEXT) ILIKE ?', ["%{$resourceId}%"]);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->where('created_at', '>=', $dateFrom . ' 00:00:00');
        }

        if ($dateTo = $request->input('date_to')) {
            $query->where('created_at', '<=', $dateTo . ' 23:59:59');
        }

        if ($date = $request->input('date')) {
            $query->whereDate('created_at', $date);
        }

        return $query;
    }
}

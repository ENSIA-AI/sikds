<?php

declare(strict_types=1);

namespace App\Domain\Audit\Services;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuditService
{
    /**
     * Persist a single audit-log entry.
     *
     * Centralized so every caller writes the same column shape
     * (event_type, user_id, user_email, resource_type, resource_id,
     * metadata, result, ip_address, user_agent, created_at).
     *
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $eventType,
        string $result = 'success',
        ?User $user = null,
        ?string $resourceType = null,
        mixed $resourceId = null,
        array $metadata = [],
        ?Request $request = null,
    ): AuditLog {
        $request ??= request();

        $resolvedUser = $user
            ?? ($request?->user() instanceof User ? $request->user() : null)
            ?? (Auth::user() instanceof User ? Auth::user() : null);

        return AuditLog::query()->create([
            'event_type'    => $eventType,
            'user_id'       => $resolvedUser?->id,
            'user_email'    => $resolvedUser?->email,
            'resource_type' => $resourceType,
            'resource_id'   => $this->normalizeResourceId($resourceId),
            'metadata'      => $metadata,
            'result'        => $result,
            'ip_address'    => $request?->ip(),
            'user_agent'    => $request?->userAgent(),
            'created_at'    => now(),
        ]);
    }

    private function normalizeResourceId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_int($value)) {
            return $value;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        return null;
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Audit\Services;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AuditService
{
    /**
     * Sentinel `previous_hash` for the first chained row (no predecessor).
     */
    public const GENESIS_HASH = '0000000000000000000000000000000000000000000000000000000000000000';

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
        User|int|null $user = null,
        ?string $resourceType = null,
        mixed $resourceId = null,
        array $metadata = [],
        ?Request $request = null,
    ): AuditLog {
        $request ??= request();
        $normalizedResourceId = $this->normalizeResourceId($resourceId);

        $resolvedUser = $this->resolveActor($user, $request, $metadata);
        $metadata = $this->enrichMetadata(
            eventType: $eventType,
            result: $result,
            user: $resolvedUser,
            resourceType: $resourceType,
            resourceId: $normalizedResourceId,
            metadata: $metadata,
            request: $request,
        );

        $attributes = [
            'event_type'    => $eventType,
            'user_id'       => $resolvedUser?->id,
            'user_email'    => $resolvedUser?->email ?? $this->actorEmailFromMetadata($metadata),
            'resource_type' => $resourceType,
            'resource_id'   => $normalizedResourceId,
            'metadata'      => $metadata,
            'result'        => $result,
            'ip_address'    => $request?->ip(),
            'user_agent'    => $request?->userAgent(),
            'created_at'    => now(),
        ];

        // Tamper-evident chaining: each row stores the previous row's hash and
        // its own hash over the canonical core payload. The transaction (plus a
        // Postgres advisory lock) serializes writers so two concurrent events
        // cannot both link to the same predecessor. See the verification counterpart:
        // `php artisan audit:verify-chain`.
        return DB::transaction(function () use ($attributes): AuditLog {
            $this->lockChain();

            $previousHash = AuditLog::query()
                ->orderByDesc('id')
                ->value('row_hash') ?? self::GENESIS_HASH;

            $attributes['previous_hash'] = $previousHash;
            $attributes['row_hash'] = self::hashRow($previousHash, $attributes);

            return AuditLog::query()->create($attributes);
        });
    }

    /**
     * Serialize chain writes. pg_advisory_xact_lock is released automatically
     * when the surrounding transaction commits or rolls back. Other drivers
     * (the SQLite test database) serialize writers at the connection level.
     *
     * Note: this serializes *all* audit writes behind one lock. Audit volume is
     * one row per user action, so contention is negligible at this scale.
     */
    private function lockChain(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("SELECT pg_advisory_xact_lock(hashtext('audit_logs.chain'))");
        }
    }

    /**
     * Compute the chained hash for an audit row.
     *
     * The hash covers the semantic core of the event — event_type, actor,
     * resource, metadata and result — plus the previous row's hash (which is
     * what makes deletion or re-ordering detectable).
     *
     * Deliberately EXCLUDED from the hash:
     *   - id / created_at / ip_address / user_agent: Postgres round-trips these
     *     through BIGSERIAL, TIMESTAMPTZ and INET, which normalize their textual
     *     representation (precision, timezone, IPv6 shortening). Including them
     *     would make an honest verify pass report false chain breaks. Their
     *     integrity is still protected by the DB immutability triggers.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function hashRow(string $previousHash, array $attributes): string
    {
        return hash('sha256', $previousHash.'|'.self::canonicalPayload($attributes));
    }

    /**
     * Build a canonical, byte-stable JSON representation of the hashed fields.
     * Metadata keys are sorted recursively because JSONB does not preserve key
     * order, so the payload re-encoded at verify time must match byte-for-byte.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function canonicalPayload(array $attributes): string
    {
        $metadata = $attributes['metadata'] ?? null;
        if (is_array($metadata)) {
            $metadata = self::ksortRecursive($metadata);
        }

        $canonical = [
            'event_type'    => (string) ($attributes['event_type'] ?? ''),
            'user_id'       => isset($attributes['user_id']) ? (int) $attributes['user_id'] : null,
            'user_email'    => $attributes['user_email'] ?? null,
            'resource_type' => $attributes['resource_type'] ?? null,
            'resource_id'   => isset($attributes['resource_id']) ? (int) $attributes['resource_id'] : null,
            'metadata'      => $metadata,
            'result'        => $attributes['result'] ?? null,
        ];

        return (string) json_encode($canonical, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * @param  array<array-key, mixed>  $array
     * @return array<array-key, mixed>
     */
    private static function ksortRecursive(array $array): array
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = self::ksortRecursive($value);
            }
        }
        ksort($array);

        return $array;
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function resolveActor(User|int|null $user, ?Request $request, array $metadata): ?User
    {
        if ($user instanceof User) {
            return $user;
        }

        if (is_int($user) && $user > 0) {
            return User::query()->find($user);
        }

        $requestUser = $request?->user();
        if ($requestUser instanceof User) {
            return $requestUser;
        }

        $authUser = Auth::user();
        if ($authUser instanceof User) {
            return $authUser;
        }

        $actorId = $this->firstInt($metadata, [
            'actor_user_id',
            'sender_user_id',
            'assigned_by',
            'created_by',
            'updated_by',
            'removed_by',
            'deactivated_by',
            'activated_by',
        ]);

        return $actorId !== null ? User::query()->find($actorId) : null;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function enrichMetadata(
        string $eventType,
        string $result,
        ?User $user,
        ?string $resourceType,
        ?int $resourceId,
        array $metadata,
        ?Request $request,
    ): array {
        $eventCode = $this->normalizeEventCode($eventType);

        $metadata['event_code'] ??= $eventCode;
        $metadata['event_version'] ??= 1;
        $metadata['event'] ??= [
            'code' => $eventCode,
            'type' => $eventType,
            'category' => Str::before($eventCode, '.'),
            'action' => Str::after($eventCode, '.'),
            'result' => $result,
        ];

        $metadata['actor_context'] ??= $this->buildActorContext($user, $metadata, $request);
        $metadata['target_context'] ??= $this->buildTargetContext($resourceType, $resourceId, $metadata);

        if (! isset($metadata['changes']) && is_array($metadata['before'] ?? null) && is_array($metadata['after'] ?? null)) {
            $metadata['changes'] = $this->diffAssociativeArrays($metadata['before'], $metadata['after']);
        }

        if ($result !== 'success') {
            $metadata['failure_reason'] ??= $this->buildFailureReason($metadata);
        }

        return $metadata;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function buildActorContext(?User $user, array $metadata, ?Request $request): array
    {
        if ($user instanceof User) {
            return array_filter([
                'type' => 'user',
                'id' => $user->id,
                'email' => $user->email,
                'name' => $user->full_name,
                'institution_id' => $user->institution_id,
                'auth_type' => $user->auth_type,
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
            ], static fn ($value): bool => $value !== null && $value !== '');
        }

        return array_filter([
            'type' => $this->actorEmailFromMetadata($metadata) ? 'anonymous' : 'system',
            'email' => $this->actorEmailFromMetadata($metadata),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
        ], static fn ($value): bool => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    private function buildTargetContext(?string $resourceType, ?int $resourceId, array $metadata): array
    {
        return array_filter([
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'user_id' => $metadata['target_user_id'] ?? $metadata['recipient_user_id'] ?? ($resourceType === 'user' ? $resourceId : null),
            'user_email' => $metadata['target_email'] ?? $metadata['recipient_email'] ?? null,
            'user_name' => $metadata['target_user_name'] ?? $metadata['recipient_name'] ?? null,
            'role_id' => $metadata['role_id'] ?? null,
            'role_name' => $metadata['role_name'] ?? null,
            'permission_id' => $metadata['permission_id'] ?? null,
            'permission_code' => $metadata['permission_code'] ?? null,
            'document_id' => $metadata['document_id'] ?? ($resourceType === 'document' ? $resourceId : null),
            'document_reference' => $metadata['reference_number'] ?? $metadata['document_reference'] ?? null,
            'document_title' => $metadata['document_title'] ?? null,
            'tag_id' => $metadata['tag_id'] ?? ($resourceType === 'tag' ? $resourceId : null),
            'tag_name' => $metadata['tag_name'] ?? null,
            'section' => $metadata['section'] ?? null,
        ], static fn ($value): bool => $value !== null && $value !== '');
    }

    /**
     * @param  array<string, mixed>  $before
     * @param  array<string, mixed>  $after
     * @return array<string, array{before: mixed, after: mixed}>
     */
    private function diffAssociativeArrays(array $before, array $after): array
    {
        $changes = [];
        foreach (array_unique([...array_keys($before), ...array_keys($after)]) as $key) {
            if (($before[$key] ?? null) !== ($after[$key] ?? null)) {
                $changes[(string) $key] = [
                    'before' => $before[$key] ?? null,
                    'after' => $after[$key] ?? null,
                ];
            }
        }

        return $changes;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @return array{code: string, message: string|null}
     */
    private function buildFailureReason(array $metadata): array
    {
        $reason = $metadata['reason'] ?? $metadata['error_code'] ?? 'unknown';
        $message = $metadata['message'] ?? $metadata['error'] ?? null;

        return [
            'code' => is_scalar($reason) ? (string) $reason : 'unknown',
            'message' => is_scalar($message) ? (string) $message : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $metadata
     */
    private function actorEmailFromMetadata(array $metadata): ?string
    {
        $email = $metadata['actor_email']
            ?? $metadata['attempted_email']
            ?? $metadata['user_email']
            ?? null;

        return is_string($email) && $email !== '' ? $email : null;
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<int, string>  $keys
     */
    private function firstInt(array $metadata, array $keys): ?int
    {
        foreach ($keys as $key) {
            $value = $metadata[$key] ?? null;
            if (is_numeric($value) && (int) $value > 0) {
                return (int) $value;
            }
        }

        return null;
    }

    private function normalizeEventCode(string $eventType): string
    {
        return str_replace('_', '.', Str::lower($eventType));
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

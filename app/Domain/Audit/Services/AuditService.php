<?php

declare(strict_types=1);

namespace App\Domain\Audit\Services;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Users\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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

        return AuditLog::query()->create([
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
        ]);
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

<?php

declare(strict_types=1);

namespace App\Domain\Users\Actions;

use App\Domain\Audit\Services\AuditService;
use App\Domain\Users\Models\User;
use Illuminate\Support\Facades\DB;

final class UpdateUserAction
{
    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function execute(User $user, array $data): User
    {
        $before = [
            'full_name'      => $user->full_name,
            'email'          => $user->email,
            'institution_id' => $user->institution_id,
            'is_active'      => (bool) $user->is_active,
        ];

        $updated = DB::transaction(function () use ($user, $data) {
            $user->update([
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'institution_id' => $data['institution_id'],
                'is_active' => $data['is_active'] ?? $user->is_active,
            ]);

            return $user->fresh('institution', 'roles');
        });

        $after = [
            'full_name'      => $updated->full_name,
            'email'          => $updated->email,
            'institution_id' => $updated->institution_id,
            'is_active'      => (bool) $updated->is_active,
        ];

        $changedKeys = array_keys(array_filter(
            $after,
            fn ($value, $key) => ($before[$key] ?? null) !== $value,
            ARRAY_FILTER_USE_BOTH
        ));

        $this->audit->record(
            eventType: 'user.updated',
            resourceType: 'user',
            resourceId: $updated->id,
            metadata: [
                'target_user_id'   => $updated->id,
                'target_user_name' => $updated->full_name,
                'before'           => $before,
                'after'            => $after,
                'changed_keys'     => $changedKeys,
            ],
        );

        return $updated;
    }
}

<?php

declare(strict_types=1);

namespace App\Support\Permissions;

/**
 * Maps permission names (prefix) to UI sections for the role creation modal.
 */
final class PermissionSections
{
    /** Display order in the modal */
    public const ORDER = [
        'documents',
        'indexing',
        'utilisateurs',
        'roles',
        'tags',
        'audit',
        'rag',
    ];

    /** Section key => French title */
    public const LABELS = [
        'documents' => 'Documents',
        'indexing' => 'Indexation',
        'utilisateurs' => 'Utilisateurs',
        'roles' => 'Roles',
        'tags' => 'Tags',
        'audit' => 'Audit',
        'rag' => 'RAG',
        'other' => 'Autres',
    ];

    public static function inferKey(string $permissionName): string
    {
        $n = strtolower($permissionName);

        if (str_starts_with($n, 'document.') || str_starts_with($n, 'distribution.')) {
            return 'documents';
        }
        if (str_starts_with($n, 'user.') || str_starts_with($n, 'institution.')) {
            return 'utilisateurs';
        }
        if (str_starts_with($n, 'indexing.')) {
            return 'indexing';
        }
        if (str_starts_with($n, 'role.')) {
            return 'roles';
        }
        if (str_starts_with($n, 'tag.')) {
            return 'tags';
        }
        if (str_starts_with($n, 'audit.')) {
            return 'audit';
        }
        if (str_starts_with($n, 'rag.') || str_starts_with($n, 'search.')) {
            return 'rag';
        }

        return 'other';
    }

    /**
     * @return list<array{key: string, label: string, permissions: list<array{id: int, name: string, label: string}>}>
     */
    public static function groupedForModal(iterable $permissions): array
    {
        $buckets = [];
        foreach (self::ORDER as $key) {
            $buckets[$key] = [];
        }
        $buckets['other'] = [];

        foreach ($permissions as $p) {
            $key = self::inferKey($p->name);
            if (! isset($buckets[$key])) {
                $buckets[$key] = [];
            }
            $buckets[$key][] = [
                'id' => (int) $p->id,
                'name' => $p->name,
                'label' => self::labelForPermission($p->name),
            ];
        }

        $out = [];
        foreach (array_merge(self::ORDER, ['other']) as $key) {
            if (empty($buckets[$key])) {
                continue;
            }
            $out[] = [
                'key' => $key,
                'label' => self::LABELS[$key] ?? $key,
                'permissions' => $buckets[$key],
            ];
        }

        return $out;
    }

    private static function labelForPermission(string $name): string
    {
        return str_replace(['.', '_'], [' — ', ' '], $name);
    }
}

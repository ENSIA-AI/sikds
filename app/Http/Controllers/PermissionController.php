<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Domain\Users\Models\Permission;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

final class PermissionController extends Controller
{
    // Display the permission catalog.
    public function index(Request $request): View
    {
        // Note: Permissions are system-defined and read-only in UI.
        // Filtering/search is handled client-side (no page reload).

        $all = Permission::query()
            ->select(['id', 'name', 'code', 'description', 'category'])
            ->orderBy('category')
            ->orderBy('name')
            ->get();

        $categoryOrder = [
            'Documents',
            'Distribution',
            'Tags',
            'Indexation',
            'Utilisateurs',
            'Roles',
            'Institutions',
            'Chatbot',
            'Audit',
            'Autres',
        ];

        

        $canonicalizeCategory = static function (string $raw): string {
            $c = trim($raw);
            if ($c === '') {
                return '';
            }

            $key = Str::of($c)->lower()->ascii()->replace(['-', '_'], ' ')->squish()->value();

            return match ($key) {
                'documents', 'document' => 'Documents',
                'distribution' => 'Distribution',
                'tags', 'tag' => 'Tags',
                'indexation', 'indexing' => 'Indexation',
                'utilisateurs', 'utilisateur', 'users', 'user' => 'Utilisateurs',
                'roles', 'role', 'roles permissions', 'roles & permissions', 'role permissions', 'roles and permissions' => 'Roles',
                'institutions', 'institution' => 'Institutions',
                'chatbot', 'chat', 'rag', 'search' => 'Chatbot',
                'audit', 'audits' => 'Audit',
                'autres', 'others', 'misc' => 'Autres',
                default => 'Autres',
            };
        };

        // Bucket by permission code/name first. DB `category` is legacy / broad buckets in seed data
        // (e.g. tag.* stored as "documents", role.* as "users") — code prefix is authoritative for the UI.
        $inferCategory = function (?string $category, ?string $code, string $fallbackName) use ($canonicalizeCategory): string {
            $v = strtolower(trim((string) ($code ?: $fallbackName)));
            if (str_starts_with($v, 'document.')) {
                return 'Documents';
            }
            if (str_starts_with($v, 'distribution.')) {
                return 'Distribution';
            }
            if (str_starts_with($v, 'tag.')) {
                return 'Tags';
            }
            if (str_starts_with($v, 'indexing.')) {
                return 'Indexation';
            }
            if (str_starts_with($v, 'user.')) {
                return 'Utilisateurs';
            }
            if (str_starts_with($v, 'role.')) {
                return 'Roles';
            }
            if (str_starts_with($v, 'institution.')) {
                return 'Institutions';
            }
            if (str_starts_with($v, 'audit.')) {
                return 'Audit';
            }
            if (str_starts_with($v, 'chatbot.') || str_starts_with($v, 'chat.') || str_starts_with($v, 'rag.') || str_starts_with($v, 'search.')) {
                return 'Chatbot';
            }

            $c = trim((string) ($category ?? ''));
            if ($c !== '') {
                return $canonicalizeCategory($c);
            }

            return 'Autres';
        };

        $permissionsByCategory = [];
        $countsByCategory = [];

        foreach ($all as $p) {
            $cat = $inferCategory($p->category, $p->code, $p->name);
            $permissionsByCategory[$cat] ??= [];
            $countsByCategory[$cat] = ($countsByCategory[$cat] ?? 0) + 1;
            $permissionsByCategory[$cat][] = [
                'id' => (int) $p->id,
                'name' => (string) $p->name,
                'code' => (string) ($p->code ?? $p->name),
                'description' => (string) ($p->description ?? ''),
            ];
        }

        // Ensure the 8 requested categories exist for UI (even if empty).
        foreach ($categoryOrder as $cat) {
            $permissionsByCategory[$cat] ??= [];
            $countsByCategory[$cat] ??= 0;
        }

        // Order categories for display and filtering.
        $orderedPermissionsByCategory = [];
        $orderedCountsByCategory = [];
        foreach ($categoryOrder as $cat) {
            $orderedPermissionsByCategory[$cat] = $permissionsByCategory[$cat] ?? [];
            $orderedCountsByCategory[$cat] = (int) ($countsByCategory[$cat] ?? 0);
        }

        $totalPermissions = $all->count();
        $categoriesCount = count(array_filter(array_keys($orderedCountsByCategory), static fn ($c) => ($orderedCountsByCategory[$c] ?? 0) > 0));

        // "Critiques" derived from DB data (not hardcoded rows): delete/destroy/revoke-like capabilities.
        $criticalPermissions = $all->filter(function ($p) {
            $code = strtolower((string) ($p->code ?? $p->name ?? ''));
            return str_ends_with($code, '.delete')
                || str_ends_with($code, '.destroy')
                || str_contains($code, '.delete.')
                || str_contains($code, '.destroy.')
                || str_contains($code, 'delete')
                || str_contains($code, 'destroy')
                || str_contains($code, 'revoke');
        })->count();

        // "Assignées" = permissions linked to at least one role.
        $assignedToRoles = Permission::query()
            ->whereHas('roles')
            ->count();

        $filters = array_map(static function (string $cat) use ($orderedCountsByCategory): array {
            return [
                'key' => $cat,
                'label' => __($cat),
                'count' => (int) ($orderedCountsByCategory[$cat] ?? 0),
            ];
        }, $categoryOrder);

        return view('permissions', [
            'title' => 'Permissions — ' . config('app.name'),
            'stats' => [
                'total_permissions' => (int) $totalPermissions,
                'categories' => (int) $categoriesCount,
                'critical' => (int) $criticalPermissions,
                'assigned' => (int) $assignedToRoles,
            ],
            'filters' => $filters,
            'permissionsByCategory' => $orderedPermissionsByCategory,
            'totalCount' => (int) $totalPermissions,
        ]);
    }
}
<?php

declare(strict_types=1);

namespace App\Http\Controllers\Common;

use App\Domain\Audit\Services\AuditService;
use App\Domain\Tags\Models\Tag;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TagsController
{
    private const CATEGORY_LABELS = [
        'type_document' => 'Type de Document',
        'priority'      => 'Priorité',
    ];

    public function __construct(
        private readonly AuditService $audit,
    ) {}

    public function index(): View
    {
        $predefinedGroups = Tag::withCount('documents')
            ->where('is_predefined', true)
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

        $customTags = Tag::withCount('documents')
            ->where('is_predefined', false)
            ->orderBy('name')
            ->get();

        $existingCategories = Tag::query()
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();

        $categoryLabels = collect($existingCategories)
            ->mapWithKeys(fn (string $c) => [$c => self::CATEGORY_LABELS[$c] ?? Str::headline($c)])
            ->all();

        $allTags = Tag::get(['id', 'name', 'color'])
            ->map(fn (Tag $t): array => [
                'id'    => $t->id,
                'name'  => $t->name,
                'color' => strtolower($t->color ?? '#e0e7ff'),
            ])
            ->all();

        return view('tags.index', compact(
            'predefinedGroups',
            'customTags',
            'categoryLabels',
            'allTags',
            'existingCategories',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeManage();

        $data = $this->validateTag($request);

        $tag = Tag::create([
            'name'          => $data['name'],
            'slug'          => Str::slug($data['name']),
            'color'         => strtolower($data['color'] ?? '#e0e7ff'),
            'category'      => $this->resolveCategory($data),
            'is_predefined' => false,
            'created_by'    => Auth::id(),
        ]);

        $this->audit->record(
            eventType: 'tag.created',
            resourceType: 'tag',
            resourceId: $tag->id,
            metadata: [
                'tag_name' => $tag->name,
                'category' => $tag->category,
                'color'    => $tag->color,
            ],
            request: $request,
        );

        return redirect()->route('tags.index')->with('success', 'Tag créé avec succès.');
    }

    public function update(Request $request, Tag $tag): RedirectResponse
    {
        $this->authorizeManage();

        $data = $this->validateTag($request, $tag);

        $before = [
            'name'     => $tag->name,
            'category' => $tag->category,
            'color'    => $tag->color,
        ];

        $tag->update([
            'name'     => $data['name'],
            'slug'     => Str::slug($data['name']),
            'color'    => strtolower($data['color'] ?? $tag->color),
            'category' => $this->resolveCategory($data),
        ]);

        $this->audit->record(
            eventType: 'tag.updated',
            resourceType: 'tag',
            resourceId: $tag->id,
            metadata: [
                'tag_name' => $tag->name,
                'before'   => $before,
                'after'    => [
                    'name'     => $tag->name,
                    'category' => $tag->category,
                    'color'    => $tag->color,
                ],
            ],
            request: $request,
        );

        return redirect()->route('tags.index')->with('success', 'Tag mis à jour.');
    }

    public function destroy(Request $request, Tag $tag): RedirectResponse
    {
        $this->authorizeManage();

        $usageCount = $tag->documents()->count();
        if ($usageCount > 0) {
            $this->audit->record(
                eventType: 'tag.deleted',
                result: 'failed',
                resourceType: 'tag',
                resourceId: $tag->id,
                metadata: [
                    'tag_name'    => $tag->name,
                    'reason'      => 'tag_in_use',
                    'usage_count' => $usageCount,
                ],
                request: $request,
            );

            return redirect()->route('tags.index')->with(
                'error',
                "Impossible de supprimer ce tag : il est utilisé par {$usageCount} document(s)."
            );
        }

        $snapshot = [
            'tag_name' => $tag->name,
            'category' => $tag->category,
            'color'    => $tag->color,
        ];
        $tagId = $tag->id;

        $tag->delete();

        $this->audit->record(
            eventType: 'tag.deleted',
            resourceType: 'tag',
            resourceId: $tagId,
            metadata: $snapshot,
            request: $request,
        );

        return redirect()->route('tags.index')->with('success', 'Tag supprimé.');
    }

    private function authorizeManage(): void
    {
        $user = Auth::user();
        abort_if($user === null || ! $user->can('tag.manage'), 403, 'Accès refusé. Permission tag.manage requise.');
    }

    private function validateTag(Request $request, ?Tag $tag = null): array
    {
        $request->merge([
            'name'  => trim((string) $request->input('name')),
            'color' => $request->input('color') ? strtolower((string) $request->input('color')) : null,
        ]);

        $normalizedName = $this->normalizeName((string) $request->input('name'));

        $uniqueName = function ($attribute, $value, $fail) use ($tag, $normalizedName): void {
            $clash = Tag::query()
                ->when($tag, fn ($q) => $q->where('id', '!=', $tag->id))
                ->get(['id', 'name'])
                ->contains(fn (Tag $t) => $this->normalizeName($t->name) === $normalizedName);
            if ($clash) {
                $fail('Un tag avec ce nom existe déjà (les accents et la casse sont ignorés).');
            }
        };

        $uniqueColor = function ($attribute, $value, $fail) use ($tag): void {
            if (! $value) {
                return;
            }
            $color = strtolower((string) $value);
            $clash = Tag::query()
                ->whereRaw('LOWER(color) = ?', [$color])
                ->when($tag, fn ($q) => $q->where('id', '!=', $tag->id))
                ->exists();
            if ($clash) {
                $fail('Cette couleur est déjà utilisée par un autre tag.');
            }
        };

        $uniqueNewCategory = function ($attribute, $value, $fail) use ($request): void {
            if ($request->input('category_mode') !== 'new') {
                return;
            }
            $raw = trim((string) $value);
            if ($raw === '') {
                $fail('Saisissez un nom pour la nouvelle catégorie.');
                return;
            }
            if ($this->categoryExists($raw)) {
                $fail('Cette catégorie existe déjà.');
            }
        };

        return $request->validate([
            'name'          => ['required', 'string', 'max:100', $uniqueName],
            'color'         => ['nullable', 'string', 'max:7', 'regex:/^#[0-9a-fA-F]{6}$/i', $uniqueColor],
            'category_mode' => ['nullable', 'in:existing,new,none'],
            'category'      => ['nullable', 'string', 'max:50'],
            'new_category'  => ['nullable', 'string', 'max:50', $uniqueNewCategory],
        ]);
    }

    private function resolveCategory(array $data): ?string
    {
        $mode = $data['category_mode'] ?? 'none';

        if ($mode === 'existing') {
            $value = trim((string) ($data['category'] ?? ''));
            return $value === '' ? null : $value;
        }

        if ($mode === 'new') {
            $new = trim((string) ($data['new_category'] ?? ''));
            return $new === '' ? null : Str::slug($new, '_');
        }

        return null;
    }

    private function categoryExists(string $input): bool
    {
        $norm = $this->normalizeName($input);
        if ($norm === '') {
            return false;
        }

        $candidateSlug = Str::slug($input, '_');

        $existing = Tag::query()
            ->whereNotNull('category')
            ->distinct()
            ->pluck('category')
            ->all();

        foreach ($existing as $slug) {
            if ($slug === $candidateSlug) {
                return true;
            }
            if ($this->normalizeName($slug) === $norm) {
                return true;
            }
            $label = self::CATEGORY_LABELS[$slug] ?? Str::headline($slug);
            if ($this->normalizeName($label) === $norm) {
                return true;
            }
        }

        foreach (self::CATEGORY_LABELS as $slug => $label) {
            if ($slug === $candidateSlug) {
                return true;
            }
            if ($this->normalizeName($label) === $norm) {
                return true;
            }
        }

        return false;
    }

    private function normalizeName(string $name): string
    {
        $ascii = Str::ascii(trim($name));
        return preg_replace('/\s+/', ' ', mb_strtolower($ascii));
    }
}

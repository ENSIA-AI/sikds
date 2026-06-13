<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Domain\Settings\Models\SystemSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class SystemSettingsService
{
    public const CACHE_KEY = 'system_settings.all';

    public function defaults(): array
    {
        return [
            'notifications' => [
                'document_published_enabled' => true,
                'document_updated_enabled' => true,
                'support_contact_email' => 'support@mesrs.dz',
            ],
            'audit' => [
                'retention_days' => 365,
                'export_max_days' => 90,
            ],
            'watermark' => [
                'visible_fields' => ['full_name', 'institution', 'timestamp', 'uuid'],
                'metadata_fields' => ['full_name', 'institution', 'email', 'timestamp', 'uuid'],
            ],
            'language' => [
                'default' => 'fr',
            ],
            // Admin-tunable RAG knobs. config/rag.php provides the defaults (single
            // source of truth); any value saved here overrides it at query time.
            // Infra/secrets (provider, url, api_key) and the authorization toggle are
            // deliberately NOT exposed here.
            'rag' => [
                'llm_model' => (string) config('rag.llm.model', 'llama-3.3-70b-versatile'),
                'min_confidence' => (float) config('rag.retrieval.min_confidence', 0.10),
                'candidate_pool' => (int) config('rag.retrieval.candidate_pool', 20),
                'reranking_enabled' => (bool) config('rag.reranking.enabled', false),
                'top_n' => (int) config('rag.reranking.top_n', 6),
                'hybrid_enabled' => (bool) config('rag.hybrid.enabled', true),
            ],
        ];
    }

    public function all(): array
    {
        // Cache ONLY the persisted overrides (a small dotted-key => value map), then
        // merge them onto a freshly-built defaults() on every call. This guarantees
        // that adding a new default section never crashes against a stale cache that
        // predates it — the defaults are always current, only the DB layer is cached.
        $overrides = Cache::rememberForever(self::CACHE_KEY, function (): array {
            return SystemSetting::query()
                ->get(['key', 'value'])
                ->mapWithKeys(static function (SystemSetting $row): array {
                    $value = $row->value;

                    return [$row->key => is_array($value) ? ($value['value'] ?? null) : null];
                })
                ->all();
        });

        $merged = $this->defaults();
        foreach ($overrides as $key => $value) {
            Arr::set($merged, $key, $value);
        }

        return $merged;
    }

    public function get(string $key, mixed $fallback = null): mixed
    {
        return Arr::get($this->all(), $key, $fallback);
    }

    public function updateSection(string $section, array $payload, int $userId): void
    {
        foreach ($payload as $field => $value) {
            SystemSetting::query()->updateOrCreate(
                ['key' => "{$section}.{$field}"],
                [
                    'value' => ['value' => $value],
                    'updated_by' => $userId,
                    'updated_at' => now(),
                ]
            );
        }

        self::flush();
    }

    public static function flush(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}


<?php

declare(strict_types=1);

namespace App\Services\Settings;

use App\Domain\Settings\Models\SystemSetting;
use Illuminate\Support\Arr;

class SystemSettingsService
{
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
        ];
    }

    public function all(): array
    {
        $defaults = $this->defaults();
        $rows = SystemSetting::query()->get(['key', 'value']);

        foreach ($rows as $row) {
            $value = $row->value;
            if (! is_array($value)) {
                continue;
            }
            Arr::set($defaults, $row->key, $value['value'] ?? null);
        }

        return $defaults;
    }

    public function get(string $key, mixed $fallback = null): mixed
    {
        $all = $this->all();

        return Arr::get($all, $key, $fallback);
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
    }
}


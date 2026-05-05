<?php

declare(strict_types=1);

namespace App\Domain\Settings\Models;

use App\Domain\Users\Models\User;
use App\Services\Settings\SystemSettingsService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SystemSetting extends Model
{
    protected $table = 'system_settings';

    public $timestamps = false;

    protected $fillable = [
        'key',
        'value',
        'updated_by',
        'updated_at',
    ];

    protected $casts = [
        'value' => 'array',
        'updated_at' => 'datetime',
        'updated_by' => 'integer',
    ];

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    protected static function booted(): void
    {
        $flush = static fn () => SystemSettingsService::flush();
        static::saved($flush);
        static::deleted($flush);
    }
}


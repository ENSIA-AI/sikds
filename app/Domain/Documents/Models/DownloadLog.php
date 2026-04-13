<?php

declare(strict_types=1);

namespace App\Domain\Documents\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DownloadLog extends Model
{
    protected $table = 'download_logs';

    public $timestamps = false; // We use downloaded_at specifically

    protected $fillable = [
        'document_id',
        'user_id',
        'watermark_uuid',
        'ip_address',
        'user_agent',
        'downloaded_at',
    ];

    protected $casts = [
        'downloaded_at' => 'datetime',
    ];

    /**
     * The document that was downloaded.
     */
    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class);
    }

    /**
     * The user who downloaded the document.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($log) {
            if (empty($log->watermark_uuid)) {
                $log->watermark_uuid = (string) Str::uuid();
            }
            if (empty($log->downloaded_at)) {
                $log->downloaded_at = now();
            }
        });
    }
}

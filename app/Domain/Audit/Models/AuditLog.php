<?php

declare(strict_types=1);

namespace App\Domain\Audit\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Domain\Users\Models\User;

class AuditLog extends Model
{
    protected $table = 'audit_logs';

    public $timestamps = false;

    protected $fillable = [
        'event_type',
        'user_id',
        'user_email',
        'resource_type',
        'resource_id',
        'metadata',
        'result',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'metadata'   => 'array',
        'created_at' => 'datetime',
        'resource_id' => 'integer',
        'user_id'     => 'integer',
    ];

    /**
     * The user who triggered this event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Documents\Models;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Pivot row representing a document directly assigned/forwarded to a user.
 *
 * The migration `2026_01_01_000008_create_document_target_tables.php` creates the
 * pivot with a unique key on (document_id, user_id) and the follow-up migration
 * `2026_05_05_000000_add_assigned_by_to_document_user_targets` adds the optional
 * traceability column populated by the forward feature.
 */
class DocumentUserTarget extends Model
{
    protected $table = 'document_user_targets';

    public $timestamps = false;

    protected $fillable = [
        'document_id',
        'user_id',
        'assigned_by',
        'created_at',
    ];

    protected $casts = [
        'document_id' => 'integer',
        'user_id' => 'integer',
        'assigned_by' => 'integer',
        'created_at' => 'datetime',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}

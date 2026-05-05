<?php

declare(strict_types=1);

namespace App\Domain\Notifications\Models;

use App\Domain\Documents\Models\Document;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $table = 'notifications';

    public $timestamps = false;

    protected $fillable = [
        'type',
        'recipient_user_id',
        'document_id',
        'email_sent_at',
        'email_status',
        'email_error',
        'metadata',
        'read_at',
        'created_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'created_at' => 'datetime',
        'email_sent_at' => 'datetime',
        'read_at' => 'datetime',
        'recipient_user_id' => 'integer',
        'document_id' => 'integer',
    ];

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_user_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }
}


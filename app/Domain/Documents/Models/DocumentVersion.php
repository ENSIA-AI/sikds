<?php

declare(strict_types=1);

namespace App\Domain\Documents\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentVersion extends Model
{
    protected $table = 'document_versions';

    public $timestamps = false;

    protected $fillable = [
        'document_id',
        'version_number',
        'file_path',
        'file_hash',
        'status',
        'metadata',
        'created_at',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }
}


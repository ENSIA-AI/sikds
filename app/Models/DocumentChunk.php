<?php

declare(strict_types=1);

namespace App\Models;

use App\Domain\Documents\Models\Document;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Stores chunked document text and its vector embedding for retrieval.
 */
class DocumentChunk extends Model
{
    protected $table = 'document_chunks';

    protected $fillable = [
        'document_id',
        'chunk_index',
        'content',
        'token_count',
        'metadata',
    ];

    protected $casts = [
        'document_id' => 'integer',
        'chunk_index' => 'integer',
        'token_count' => 'integer',
        'metadata' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function scopeForDocument($query, int $documentId)
    {
        return $query->where('document_id', $documentId);
    }

    public function scopeMissingEmbeddings($query)
    {
        return $query->whereNull('embedding');
    }
}


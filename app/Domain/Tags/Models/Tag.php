<?php

declare(strict_types=1);

namespace App\Domain\Tags\Models;

use App\Domain\Documents\Models\Document;
use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tag extends Model
{
    protected $table = 'tags';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'color',
        'category',
        'parent_id',
        'is_predefined',
        'created_by',
    ];

    protected $casts = [
        'is_predefined' => 'boolean',
    ];

    public function documents(): BelongsToMany
    {
        return $this->belongsToMany(Document::class, 'document_tags', 'tag_id', 'document_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Tag::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Tag::class, 'parent_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

<?php

declare(strict_types=1);

namespace App\Domain\Users\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DocumentInstitutionTarget extends Model
{
    protected $table = 'document_institution_targets';

    public $timestamps = false;

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }
}

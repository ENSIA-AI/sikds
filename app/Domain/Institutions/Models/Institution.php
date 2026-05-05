<?php

declare(strict_types=1);

namespace App\Domain\Institutions\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Institution extends Model
{
    protected $table = 'institutions';

    protected $fillable = [
        'code',
        'name',
        'type',
        'domain',
        'contact_email',
        'contact_phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Users belonging to this institution.
     */
    public function users(): HasMany
    {
        return $this->hasMany(\App\Domain\Users\Models\User::class, 'institution_id');
    }
}

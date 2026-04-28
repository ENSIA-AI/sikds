<?php

     /** repre4sents roles that group permissions, so we can assign roles to users 
     * to get their permissions     
     */

declare(strict_types=1);

namespace App\Domain\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasFactory;

    
    protected $fillable = [
        'name',
        'guard_name',
        'slug',
        'description',
        'is_system_role',
        'created_by',
    ];


    protected $casts = [
        'is_system_role' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

   
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    
    public function scopeCustom($query)
    {
        return $query->where('is_system_role', false);
    }


    public function scopeSystem($query)
    {
        return $query->where('is_system_role', true);
    }

    
    public function isSystemRole(): bool
    {
        return $this->is_system_role;
    }

    
    public function isDeletable(): bool
    {
        // System roles cannot be deleted
        if ($this->is_system_role) {
            return false;
        }

        // Roles with assigned users cannot be deleted
        if ($this->users()->count() > 0) {
            return false;
        }

        return true;
    }

    
    public function isEditable(): bool
    {
        // System roles cannot be edited
        return !$this->is_system_role;
    }

    protected static function newFactory(): Factory
    {
        return \Database\Factories\Domain\Users\Models\RoleFactory::new();
    }
}

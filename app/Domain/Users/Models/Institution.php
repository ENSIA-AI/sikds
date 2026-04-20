<?php
    /** represent institutions that users belong to

    */

declare(strict_types=1);

namespace App\Domain\Users\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Institution extends Model
{
    use HasFactory;

   
    protected $fillable = [
        'code',
        'name',
        'type',
        'domain',
        'contact_email',
        'contact_phone',
        'address',
        'is_active',
        'logo_path',
    ];

    
    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function documentInstitutionTargets(): HasMany
    {
        return $this->hasMany(DocumentInstitutionTarget::class);
    }

    protected function logoPublicUrl(): Attribute
    {
        return Attribute::make(
            get: fn (): ?string => $this->logo_path
                ? Storage::disk('public')->url($this->logo_path)
                : null,
        );
    }

    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }

    
    public function isMinistry(): bool
    {
        return $this->type === 'ministry';
    }

    
    public function isUniversity(): bool
    {
        return $this->type === 'university';
    }

    
    public function getActiveUsersCountAttribute(): int
    {
        return $this->users()->where('is_active', true)->count();
    }

    protected static function newFactory(): Factory
    {
        return \Database\Factories\Domain\Users\Models\InstitutionFactory::new();
    }
}
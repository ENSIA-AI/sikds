<?php

declare(strict_types=1);

namespace App\Domain\Users\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles;

    protected $table = 'users';

    protected $fillable = [
        'sso_user_id',
        'username',
        'email',
        'full_name',
        'institution_id',
        'auth_type',
        'auth_domain',
        'sso_profile',
        'password',
        'is_active',
        'last_login_at',
        'created_by',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_login_at' => 'datetime',
        'sso_profile' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function institution(): BelongsTo
    {
        return $this->belongsTo(Institution::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeInactive($query)
    {
        return $query->where('is_active', false);
    }

    public function scopeInstitution($query, Institution $institutionId)
    {
        return $query->where('institution_id', $institutionId);
    }

    public function useSso(): bool
    {
        return $this->auth_type === 'sso';
    }

    public function usesLocalAuth():bool
    {
        return $this->auth_type === 'local';
    }

    public function getAllPermissions()
    {
        return $this->getAllPermissionsViaRoles();
    }

    public function isMinistryUser(): bool
    {
        return $this->institution && $this->institution->isMinistry();
    }

    public function isUniversityUser(): bool
    {
        return $this->institution && $this->institution->isUniversity();
    }
    
    protected static function newFactory(): Factory
    {
        return \Database\Factories\UserFactory::new();
    }

    public function initials(): string
    {
        $source = trim((string) ($this->full_name ?: $this->username ?: $this->email));
        $words = preg_split('/\s+/u', $source, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($words) >= 2) {
            $first = mb_substr($words[0], 0, 1);
            $last = mb_substr($words[count($words) - 1], 0, 1);

            return mb_strtoupper($first.$last);
        }

        return mb_strtoupper(mb_substr($source, 0, min(2, mb_strlen($source))));
    }

    protected function fullName(): Attribute
    {
        return Attribute::make(
            get: fn (): string => (string) ($this->attributes['full_name'] ?? ''),
            set: fn (string $value): array => ['full_name' => $value],
        );
    }
}

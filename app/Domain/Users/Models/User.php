<?php

declare(strict_types=1);

namespace App\Domain\Users\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    protected $fillable = [
        'sso_user_id',
        'username',
        'email',
        'full_name',
        'institution_id',
        'auth_type',
        'auth_domain',
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
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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

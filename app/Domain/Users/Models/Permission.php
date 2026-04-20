<?php
    /** reprsents the system permissions
     * represents the individual permissions in the system ( document.create, role.edit ...)
     * 
     */

declare(strict_types=1);
namespace App\Domain\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Contracts\Permission as PermissionContract;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
use Spatie\Permission\Guard;
use Spatie\Permission\Models\Permission as SpatiePermission;

class Permission extends SpatiePermission
{
    use HasFactory;
  

    protected $fillable = [
            'name',
            'guard_name',
            'code',
            'description',
            'category',
    ];

    protected $casts = [
            'created_at' => 'datetime',
    ];

    public static function groupedByCategory()
    {
            return static::query()
            ->orderBy('category')
            ->orderBy('name')
            ->get()
            ->groupBy('category');

    }
    public function scopeCategory($query, string $category)
    {
            return $query->where('category', $category);
    }

    protected static function newFactory(): Factory
    {
        return \Database\Factories\Domain\Users\Models\PermissionFactory::new();
    }

    public static function findByName(string $name, ?string $guardName = null): PermissionContract
    {
        $guardName ??= Guard::getDefaultName(static::class);

        $permission = static::query()
            ->where('guard_name', $guardName)
            ->where(static function ($q) use ($name) {
                $q->where('code', $name)->orWhere('name', $name);
            })
            ->first();

        if (! $permission) {
            throw PermissionDoesNotExist::create($name, $guardName);
        }

        return $permission;
    }
}
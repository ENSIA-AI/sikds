<?php
    /** reprsents the system permissions
     * represents the individual permissions in the system ( document.create, role.edit ...)
     * 
     */

declare(strict_types=1);
namespace App\Domain\Users\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Permission\Models\Permission as SpatiePermission;
use Illuminate\Database\Eloquent\Model;

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
}
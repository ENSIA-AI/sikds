<?php

declare(strict_types=1);

namespace Database\Factories\Domain\Users\Models;

use App\Domain\Users\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Permission>
 */
final class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        $category = fake()->randomElement(['users', 'roles', 'permissions', 'documents']);
        $action = fake()->randomElement(['view', 'create', 'update', 'delete', 'manage']);
        $resource = Str::singular($category);
        $code = fake()->unique()->lexify("{$resource}.{$action}.????");

        return [
            'name' => Str::headline(str_replace('.', ' ', $code)),
            'guard_name' => 'web',
            'code' => $code,
            'description' => fake()->optional()->sentence(),
            'category' => $category,
        ];
    }
}


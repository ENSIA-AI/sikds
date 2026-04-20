<?php

declare(strict_types=1);

namespace Database\Factories\Domain\Users\Models;

use App\Domain\Users\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Role>
 */
final class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        $name = fake()->unique()->jobTitle();

        return [
            'name' => $name,
            'guard_name' => 'web',
            'slug' => Str::slug($name),
            'description' => fake()->optional()->sentence(),
            'is_system_role' => false,
            'created_by' => null,
        ];
    }

    public function system(): static
    {
        return $this->state(fn (): array => ['is_system_role' => true]);
    }
}


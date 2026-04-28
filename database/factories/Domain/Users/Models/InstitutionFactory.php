<?php

declare(strict_types=1);

namespace Database\Factories\Domain\Users\Models;

use App\Domain\Users\Models\Institution;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Institution>
 */
final class InstitutionFactory extends Factory
{
    protected $model = Institution::class;

    public function definition(): array
    {
        $code = strtoupper(fake()->unique()->bothify('INST###'));

        return [
            'code' => $code,
            'name' => fake()->company(),
            'type' => fake()->randomElement(['ministry', 'university']),
            'domain' => fake()->unique()->domainName(),
            'contact_email' => fake()->unique()->safeEmail(),
            'contact_phone' => fake()->phoneNumber(),
            'is_active' => true,
        ];
    }

    public function ministry(): static
    {
        return $this->state(fn (): array => ['type' => 'ministry']);
    }

    public function university(): static
    {
        return $this->state(fn (): array => ['type' => 'university']);
    }

    public function inactive(): static
    {
        return $this->state(fn (): array => ['is_active' => false]);
    }
}


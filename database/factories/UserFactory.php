<?php

namespace Database\Factories;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    public function definition(): array
    {
        $email = fake()->unique()->safeEmail();

        return [
            'sso_user_id' => fake()->unique()->userName(),
            'username' => fake()->unique()->userName(),
            'email' => $email,
            'full_name' => fake()->name(),
            'institution_id' => fn () => DB::table('institutions')->where('code', 'MESRS')->value('id')
                ?? DB::table('institutions')->insertGetId([
                    'code' => 'TEST',
                    'name' => 'Test Institution',
                    'type' => 'university',
                    'domain' => 'test.dz',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
            'auth_type' => 'sso',
            'auth_domain' => substr(strrchr($email, '@') ?: '', 1) ?: null,
            'password' => null,
            'is_active' => true,
            'last_login_at' => now(),
            'created_by' => null,
        ];
    }

    public function local(): static
    {
        return $this->state(fn (): array => [
            'auth_type' => 'local',
            'password' => bcrypt('password'),
        ]);
    }
}

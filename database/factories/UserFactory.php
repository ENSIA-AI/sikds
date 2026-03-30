<?php

namespace Database\Factories;

use App\Domain\Users\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $email = fake()->unique()->safeEmail();

        return [
            'sso_user_id' => fake()->unique()->userName(),
            'username' => fake()->unique()->userName(),
            'email' => $email,
            'full_name' => fake()->name(),
            'institution_id' => fn() => DB::table('institutions')->first()?->id ?? DB::table('institutions')->insertGetId([
                'code' => 'TEST',
                'name' => 'Test Institution',
                'type' => 'university',
                'created_at' => now(),
                'updated_at' => now(),
            ]),
            'auth_type' => 'sso',
            'auth_domain' => substr(strrchr($email, '@') ?: '', 1) ?: null,
            'password' => static::$password ??= Hash::make('password'),
            'is_active' => true,
            'last_login_at' => now(),
            'created_by' => null,
        ];
    }
}

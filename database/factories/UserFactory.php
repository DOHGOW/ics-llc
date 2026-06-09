<?php

namespace Database\Factories;

use App\Models\Core\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * UserFactory reconciled to the ICS `core_users` schema (B11). The default skeleton factory targets
 * `App\Models\User` / `users`; this one targets `App\Models\Core\User` / `core_users` with the ICS
 * columns (locale/timezone/status; tenant_id left null — ICS-owned users are NULL in Phase 1, D-004).
 *
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    private static ?string $password = null;

    public function definition(): array
    {
        return [
            'tenant_id' => null,
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => self::$password ??= Hash::make('password'),
            'locale' => 'en',
            'timezone' => 'UTC',
            'status' => 'active',
            'remember_token' => Str::random(10),
        ];
    }

    /** Unverified-email state. */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => ['email_verified_at' => null]);
    }

    /** Pending-approval state (D-047). */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => ['status' => 'pending']);
    }
}

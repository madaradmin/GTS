<?php

namespace Database\Factories;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected $model = User::class;

    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'role_id' => Role::where('slug', 'apoc')->value('id') ?? Role::factory(),
            'badge_number' => 'GAF-'.fake()->unique()->numberBetween(1000, 9999),
            'is_active' => true,
        ];
    }

    /**
     * Usuario con rol Operador APOC.
     */
    public function apoc(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::where('slug', 'apoc')->value('id') ?? Role::factory()->create(['slug' => 'apoc', 'name' => 'Operador APOC']),
        ]);
    }

    /**
     * Usuario con rol Operador APOC-CCO.
     */
    public function apocCco(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::where('slug', 'apoc-cco')->value('id') ?? Role::factory()->create(['slug' => 'apoc-cco', 'name' => 'APOC-CCO']),
        ]);
    }

    /**
     * Usuario con rol Operador APOC-CSA.
     */
    public function apocCsa(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::where('slug', 'apoc-csa')->value('id') ?? Role::factory()->create(['slug' => 'apoc-csa', 'name' => 'APOC-CSA']),
        ]);
    }

    /**
     * Usuario con rol Especialista GTS.
     */
    public function gts(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::where('slug', 'gts')->value('id') ?? Role::factory()->create(['slug' => 'gts', 'name' => 'Especialista GTS']),
        ]);
    }

    /**
     * Usuario con rol Administrador.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role_id' => Role::where('slug', 'admin')->value('id') ?? Role::factory()->create(['slug' => 'admin', 'name' => 'Administrador']),
        ]);
    }

    /**
     * Estado para usuario inactivo.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}

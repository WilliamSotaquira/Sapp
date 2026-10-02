<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
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
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (\App\Models\User $user) {
            $company = \App\Models\Company::factory()->create();
            $user->companies()->syncWithoutDetaching([$company->id]);
        });
    }

    /**
     * Rol administrador (líder del proceso). Autentica y pasa before() en toda Policy.
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'admin',
        ]);
    }

    /**
     * Rol técnico autorizado. canAccessPanel() = true; sujeto a aislamiento por Policy.
     */
    public function technicianRole(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'technician',
        ]);
    }

    /**
     * Usuario plano (role=user): sin acceso al panel (fail-closed en canAccessPanel()).
     */
    public function plainUser(): static
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'user',
        ]);
    }

    /**
     * Neutraliza el efecto del afterCreating de configure() que adjunta una
     * Company autogenerada. En vez de condicionar aquella clausura (que no
     * sobreviviría al encadenado de estados por newInstance()), registra un
     * afterCreating POSTERIOR que desvincula la compañía espuria. Los asserts
     * de scoping (§10.4/§10.5) dependen de que el usuario solo tenga las
     * entidades que el test adjunta explícitamente.
     */
    public function withoutCompany(): static
    {
        return $this->afterCreating(function (\App\Models\User $user) {
            $user->companies()->detach();
        });
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

<?php

namespace Database\Factories;

use App\Models\Clinic;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Clinic>
 */
class ClinicFactory extends Factory
{
    protected $model = Clinic::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->company(),
            'direccion' => fake()->address(),
            'telefono' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'ciudad' => fake()->city(),
            'provincia' => fake()->state(),
            'codigo_postal' => fake()->postcode(),
            'web' => fake()->url(),
            'cuit' => fake()->numerify('##-########-#'),
        ];
    }
}

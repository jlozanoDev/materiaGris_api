<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Patient::class;

    /**
     * Define the model's default state.
     *
     * @return array<string,mixed>
     */
    public function definition(): array
    {
        $fakerEs = \Faker\Factory::create('es_ES');

        $firstName = $fakerEs->firstName();
        $lastName = $fakerEs->lastName();
        $secondLastName = $fakerEs->lastName();

        return [
            'medical_record_number' => strtoupper($fakerEs->unique()->bothify('MR-####')),
            'national_id' => strtoupper($fakerEs->unique()->bothify('DNI-########')),
            'first_name' => $firstName,
            'last_name' => $lastName,
            'second_last_name' => $secondLastName,
            'gender' => $fakerEs->randomElement(['Masculino', 'Femenino', 'Otro']),
            'date_of_birth' => $fakerEs->dateTimeBetween('-90 years', '-1 years')->format('Y-m-d'),
            'city' => $fakerEs->city(),
            'insurance_id' => null,
            'is_active' => $fakerEs->boolean(90),
            'last_visit_at' => $fakerEs->dateTimeBetween('-3 years', 'now')->format('Y-m-d H:i:s'),

            // Contact
            'email' => $fakerEs->unique()->safeEmail(),
            'phone' => $fakerEs->phoneNumber(),
            'mobile' => $fakerEs->phoneNumber(),
            'contact_name' => $fakerEs->name(),
            'contact_phone' => $fakerEs->phoneNumber(),

            // Address
            'address_line1' => $fakerEs->streetAddress(),
            'address_line2' => $fakerEs->secondaryAddress(),
            'neighborhood' => $fakerEs->streetName(),
            'postal_code' => $fakerEs->postcode(),
            'state' => $fakerEs->randomElement([
                'Andalucía','Cataluña','Comunidad de Madrid','Comunidad Valenciana',
                'Galicia','País Vasco','Castilla y León','Castilla-La Mancha',
                'Extremadura','Región de Murcia','Aragón','Canarias','Islas Baleares',
                'La Rioja','Navarra','Cantabria'
            ]),
            'country' => 'España',

            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}

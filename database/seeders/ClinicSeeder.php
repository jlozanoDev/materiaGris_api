<?php

namespace Database\Seeders;

use App\Models\Clinic;
use Illuminate\Database\Seeder;

class ClinicSeeder extends Seeder
{
    public function run(): void
    {
        Clinic::create([
            'nombre' => 'Materia Gris',
            'direccion' => '',
            'telefono' => '',
            'email' => '',
            'ciudad' => '',
            'provincia' => '',
            'codigo_postal' => '',
            'web' => '',
            'cuit' => '',
        ]);
    }
}

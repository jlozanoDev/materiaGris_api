<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        // Seeder para usuario de pruebas
        $this->call(TestUserSeeder::class);
        // Seeder para pacientes (200 registros en español)
        $this->call(PatientsSeeder::class);
        // Asignar rol de administrador al usuario de pruebas
        $this->call(AssignAdminRoleToTestUserSeeder::class);
        // Usuario con rol Profesional
        $this->call(ProfessionalUserSeeder::class);
        // Plantillas de informes médicos
        $this->call(ReportTemplatesSeeder::class);
    }
}

<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Patient;
use Illuminate\Support\Facades\DB;

class PatientsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Limpiar la tabla antes de semillar para evitar duplicados
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Patient::truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        // Crear 200 pacientes con textos en español
        for ($i = 1; $i <= 200; $i++) {
            Patient::factory()->create([
                'medical_record_number' => 'MR-' . str_pad($i, 4, '0', STR_PAD_LEFT),
                'national_id' => 'DNI-' . str_pad(10000000 + $i, 8, '0', STR_PAD_LEFT),
            ]);
        }
    }
}

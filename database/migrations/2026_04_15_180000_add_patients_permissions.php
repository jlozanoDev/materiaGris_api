<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('permission_categories')->insert([
            [
                'name' => 'Pacientes',
                'slug' => 'pacientes',
                'description' => 'Permisos relacionados con la gestión de pacientes.',
                'order' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $categoryId = DB::table('permission_categories')->where('slug', 'pacientes')->value('id');

        if ($categoryId) {
            DB::table('permissions')->insert([
                [
                    'category_id' => $categoryId,
                    'name' => 'Ver Pacientes',
                    'slug' => 'patient.view',
                    'action' => 'view',
                    'description' => 'Permite ver y consultar el listado de pacientes.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'category_id' => $categoryId,
                    'name' => 'Crear Pacientes',
                    'slug' => 'patient.create',
                    'action' => 'create',
                    'description' => 'Permite dar de alta nuevos pacientes.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'category_id' => $categoryId,
                    'name' => 'Modificar Pacientes',
                    'slug' => 'patient.update',
                    'action' => 'update',
                    'description' => 'Permite modificar los datos de los pacientes.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('permissions')->whereIn('slug', ['patient.view', 'patient.create', 'patient.update'])->delete();
        DB::table('permission_categories')->where('slug', 'pacientes')->delete();
    }
};

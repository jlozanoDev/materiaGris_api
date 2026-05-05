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
        $categoryId = DB::table('permission_categories')->where('slug', 'conf-users')->value('id');

        if ($categoryId) {
            DB::table('permissions')->insert([
                [
                    'category_id' => $categoryId,
                    'name' => 'Ver Usuarios',
                    'slug' => 'admin.user.view',
                    'action' => 'view',
                    'description' => 'Permite ver y consultar el listado de usuarios del sistema.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'category_id' => $categoryId,
                    'name' => 'Crear Usuarios',
                    'slug' => 'admin.user.create',
                    'action' => 'create',
                    'description' => 'Permite crear nuevos usuarios en el sistema.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'category_id' => $categoryId,
                    'name' => 'Modificar Usuarios',
                    'slug' => 'admin.user.update',
                    'action' => 'update',
                    'description' => 'Permite modificar los datos de los usuarios existentes.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'category_id' => $categoryId,
                    'name' => 'Eliminar Usuarios',
                    'slug' => 'admin.user.delete',
                    'action' => 'delete',
                    'description' => 'Permite dar de baja o eliminar usuarios del sistema.',
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
        DB::table('permissions')->whereIn('slug', ['admin.user.view', 'admin.user.create', 'admin.user.update', 'admin.user.delete'])->delete();
    }
};

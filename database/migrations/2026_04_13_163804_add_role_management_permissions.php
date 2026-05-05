<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $categoryId = DB::table('permission_categories')->where('slug', 'conf-roles')->value('id');

        if ($categoryId) {
            DB::table('permissions')->insert([
                [
                    'category_id' => $categoryId,
                    'name' => 'Ver Roles',
                    'slug' => 'admin.role.view',
                    'action' => 'view',
                    'description' => 'Permite ver y consultar el listado de roles del sistema.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'category_id' => $categoryId,
                    'name' => 'Crear Roles',
                    'slug' => 'admin.role.create',
                    'action' => 'create',
                    'description' => 'Permite crear nuevos roles en el sistema.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'category_id' => $categoryId,
                    'name' => 'Modificar Roles',
                    'slug' => 'admin.role.update',
                    'action' => 'update',
                    'description' => 'Permite modificar los datos de los roles existentes.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                [
                    'category_id' => $categoryId,
                    'name' => 'Eliminar Roles',
                    'slug' => 'admin.role.delete',
                    'action' => 'delete',
                    'description' => 'Permite eliminar roles del sistema.',
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
        DB::table('permissions')->whereIn('slug', ['admin.role.view', 'admin.role.create', 'admin.role.update', 'admin.role.delete'])->delete();
    }
};

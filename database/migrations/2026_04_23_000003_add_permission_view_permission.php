<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $categoryId = DB::table('permission_categories')->where('slug', 'conf-roles')->value('id');

        if ($categoryId) {
            DB::table('permissions')->insert([
                [
                    'category_id' => $categoryId,
                    'name' => 'Ver Permisos',
                    'slug' => 'admin.permission.view',
                    'action' => 'view',
                    'description' => 'Permite ver y consultar el listado de todos los permisos del sistema.',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            ]);
            
            // Assign to admin role
            $roleId = DB::table('roles')->where('slug', 'admin')->value('id');
            $permissionId = DB::table('permissions')->where('slug', 'admin.permission.view')->value('id');
            
            if ($roleId && $permissionId) {
                DB::table('role_permissions')->insert([
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                    'grant' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('permissions')->where('slug', 'admin.permission.view')->delete();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create top level category
        $adminId = DB::table('permission_categories')->insertGetId([
            'name' => 'Administración',
            'slug' => 'admin',
            'description' => 'Categoría principal para administración del sistema.',
            'order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Nest existing categories
        DB::table('permission_categories')
            ->whereIn('slug', ['conf-users', 'conf-roles'])
            ->update(['parent_id' => $adminId]);
            
        // 3. Create a third level for testing: "Seguridad Avanzada" under "Roles"
        $rolesId = DB::table('permission_categories')->where('slug', 'conf-roles')->value('id');
        if ($rolesId) {
             DB::table('permission_categories')->insert([
                'name' => 'Seguridad Avanzada',
                'slug' => 'conf-roles-advanced',
                'description' => 'Permisos de seguridad crítica.',
                'order' => 1,
                'parent_id' => $rolesId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('permission_categories')
            ->whereIn('slug', ['conf-users', 'conf-roles'])
            ->update(['parent_id' => null]);
            
        DB::table('permission_categories')->where('slug', 'conf-roles-advanced')->delete();
        DB::table('permission_categories')->where('slug', 'admin')->delete();
    }
};

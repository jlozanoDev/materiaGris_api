<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create the role 'Administrador'
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'Administrador',
            'slug' => 'admin',
            'description' => 'Rol con acceso total a la gestión de usuarios y administración del sistema.',
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Get all user management permissions
        $permissions = DB::table('permissions')
            ->where('slug', 'like', 'admin.user.%')
            ->get();

        // 3. Assign them to the role
        foreach ($permissions as $permission) {
            DB::table('role_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permission->id,
                'grant' => 1, // 1 = allow
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $role = DB::table('roles')->where('slug', 'admin')->first();
        
        if ($role) {
            DB::table('role_permissions')->where('role_id', $role->id)->delete();
            DB::table('roles')->where('id', $role->id)->delete();
        }
    }
};

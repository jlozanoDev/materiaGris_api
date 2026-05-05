<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $role = DB::table('roles')->where('slug', 'admin')->first();
        if (!$role) return;

        $permissions = DB::table('permissions')
            ->where('slug', 'like', 'admin.role.%')
            ->get();

        foreach ($permissions as $permission) {
            DB::table('role_permissions')->updateOrInsert(
                [
                    'role_id' => $role->id,
                    'permission_id' => $permission->id,
                ],
                [
                    'grant' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $role = DB::table('roles')->where('slug', 'admin')->first();
        if (!$role) return;

        $permissionIds = DB::table('permissions')
            ->where('slug', 'like', 'admin.role.%')
            ->pluck('id');

        DB::table('role_permissions')
            ->where('role_id', $role->id)
            ->whereIn('permission_id', $permissionIds)
            ->delete();
    }
};

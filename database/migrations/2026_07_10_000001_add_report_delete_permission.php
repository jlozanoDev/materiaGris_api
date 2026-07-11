<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $categoryId = DB::table('permission_categories')->where('slug', 'informes')->value('id');
        if (!$categoryId) return;

        $permissionId = DB::table('permissions')->insertGetId([
            'category_id' => $categoryId,
            'name' => 'Eliminar informes',
            'slug' => 'report.delete',
            'action' => 'delete',
            'description' => 'Permite eliminar informes en estado borrador.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $role = DB::table('roles')->where('slug', 'professional')->first();
        if (!$role) return;

        DB::table('role_permissions')->updateOrInsert(
            [
                'role_id' => $role->id,
                'permission_id' => $permissionId,
            ],
            [
                'grant' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        $role = DB::table('roles')->where('slug', 'professional')->first();
        if ($role) {
            $permissionId = DB::table('permissions')->where('slug', 'report.delete')->value('id');
            if ($permissionId) {
                DB::table('role_permissions')
                    ->where('role_id', $role->id)
                    ->where('permission_id', $permissionId)
                    ->delete();
            }
        }

        DB::table('permissions')->where('slug', 'report.delete')->delete();
    }
};

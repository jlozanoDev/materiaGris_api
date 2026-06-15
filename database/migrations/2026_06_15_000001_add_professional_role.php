<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $roleId = DB::table('roles')->insertGetId([
            'name' => 'Profesional',
            'slug' => 'professional',
            'description' => 'Rol con permisos sobre pacientes e informes para profesionales médicos.',
            'is_system' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $slugs = [
            'patient.view',
            'patient.create',
            'patient.update',
            'report.view',
            'report.create',
            'report.edit',
            'report.sign',
            'report.close',
            'report.download-pdf',
        ];

        $permissionIds = DB::table('permissions')
            ->whereIn('slug', $slugs)
            ->pluck('id');

        foreach ($permissionIds as $permissionId) {
            DB::table('role_permissions')->insert([
                'role_id' => $roleId,
                'permission_id' => $permissionId,
                'grant' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        $role = DB::table('roles')->where('slug', 'professional')->first();

        if ($role) {
            DB::table('role_permissions')->where('role_id', $role->id)->delete();
            DB::table('roles')->where('id', $role->id)->delete();
        }
    }
};

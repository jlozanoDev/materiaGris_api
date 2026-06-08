<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $adminId = DB::table('permission_categories')->where('slug', 'admin')->value('id');
        if (!$adminId) return;

        $categoryId = DB::table('permission_categories')->insertGetId([
            'name' => 'Tipos informes',
            'slug' => 'conf-tipos-informes',
            'description' => 'Permisos relacionados con la gestión de tipos de informes.',
            'order' => 12,
            'parent_id' => $adminId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissions = [
            [
                'name' => 'Ver tipos informes',
                'slug' => 'admin.tipoinforme.view',
                'action' => 'view',
                'description' => 'Permite ver y consultar el listado de tipos de informes.',
            ],
            [
                'name' => 'Crear tipos informes',
                'slug' => 'admin.tipoinforme.create',
                'action' => 'create',
                'description' => 'Permite crear nuevos tipos de informes.',
            ],
            [
                'name' => 'Modificar tipos informes',
                'slug' => 'admin.tipoinforme.update',
                'action' => 'update',
                'description' => 'Permite modificar los datos de los tipos de informes existentes.',
            ],
            [
                'name' => 'Eliminar tipos informes',
                'slug' => 'admin.tipoinforme.delete',
                'action' => 'delete',
                'description' => 'Permite eliminar tipos de informes del sistema.',
            ],
        ];

        foreach ($permissions as $data) {
            $data['category_id'] = $categoryId;
            $data['created_at'] = now();
            $data['updated_at'] = now();

            DB::table('permissions')->insert($data);
        }

        $role = DB::table('roles')->where('slug', 'admin')->first();
        if (!$role) return;

        $slugs = array_column($permissions, 'slug');
        $ids = DB::table('permissions')->whereIn('slug', $slugs)->pluck('id');

        foreach ($ids as $permissionId) {
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
    }

    public function down(): void
    {
        $slugs = [
            'admin.tipoinforme.view',
            'admin.tipoinforme.create',
            'admin.tipoinforme.update',
            'admin.tipoinforme.delete',
        ];

        $role = DB::table('roles')->where('slug', 'admin')->first();
        if ($role) {
            $permissionIds = DB::table('permissions')
                ->whereIn('slug', $slugs)
                ->pluck('id');

            DB::table('role_permissions')
                ->where('role_id', $role->id)
                ->whereIn('permission_id', $permissionIds)
                ->delete();
        }

        DB::table('permissions')->whereIn('slug', $slugs)->delete();
        DB::table('permission_categories')->where('slug', 'conf-tipos-informes')->delete();
    }
};

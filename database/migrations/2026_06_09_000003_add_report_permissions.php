<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $parentId = DB::table('permission_categories')->where('slug', 'pacientes')->value('id');
        if (!$parentId) return;

        $categoryId = DB::table('permission_categories')->insertGetId([
            'name' => 'Informes',
            'slug' => 'informes',
            'description' => 'Permisos relacionados con los informes dinámicos de pacientes.',
            'order' => 21,
            'parent_id' => $parentId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $permissions = [
            [
                'name' => 'Ver informes',
                'slug' => 'report.view',
                'action' => 'view',
                'description' => 'Permite ver y consultar el listado de informes de pacientes.',
            ],
            [
                'name' => 'Crear informes',
                'slug' => 'report.create',
                'action' => 'create',
                'description' => 'Permite crear nuevos informes para pacientes.',
            ],
            [
                'name' => 'Editar informes',
                'slug' => 'report.edit',
                'action' => 'update',
                'description' => 'Permite editar informes en estado borrador.',
            ],
            [
                'name' => 'Firmar informes',
                'slug' => 'report.sign',
                'action' => 'sign',
                'description' => 'Permite firmar informes de pacientes.',
            ],
            [
                'name' => 'Cerrar informes',
                'slug' => 'report.close',
                'action' => 'close',
                'description' => 'Permite cerrar y generar PDF de informes.',
            ],
            [
                'name' => 'Descargar PDF',
                'slug' => 'report.download-pdf',
                'action' => 'download',
                'description' => 'Permite descargar el PDF de informes cerrados.',
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
            'report.view',
            'report.create',
            'report.edit',
            'report.sign',
            'report.close',
            'report.download-pdf',
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
        DB::table('permission_categories')->where('slug', 'informes')->delete();
    }
};

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
        DB::table('permission_categories')->insert([
            [
                'name' => 'Usuarios',
                'slug' => 'conf-users',
                'description' => 'Permisos relacionados con la gestión de usuarios del sistema.',
                'order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Roles y Permisos',
                'slug' => 'conf-roles',
                'description' => 'Permisos relacionados con la gestión de roles y asignación de permisos.',
                'order' => 11,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('permission_categories')->whereIn('slug', ['conf-users', 'conf-roles'])->delete();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Rename permission category slug
        DB::table('permission_categories')
            ->where('slug', 'conf-tipos-informes')
            ->update(['slug' => 'conf-report-templates']);

        // Rename permission slugs
        DB::table('permissions')
            ->where('slug', 'admin.tipoinforme.view')
            ->update(['slug' => 'admin.reporttemplate.view']);

        DB::table('permissions')
            ->where('slug', 'admin.tipoinforme.create')
            ->update(['slug' => 'admin.reporttemplate.create']);

        DB::table('permissions')
            ->where('slug', 'admin.tipoinforme.update')
            ->update(['slug' => 'admin.reporttemplate.update']);

        DB::table('permissions')
            ->where('slug', 'admin.tipoinforme.delete')
            ->update(['slug' => 'admin.reporttemplate.delete']);
    }

    public function down(): void
    {
        DB::table('permission_categories')
            ->where('slug', 'conf-report-templates')
            ->update(['slug' => 'conf-tipos-informes']);

        DB::table('permissions')
            ->where('slug', 'admin.reporttemplate.view')
            ->update(['slug' => 'admin.tipoinforme.view']);

        DB::table('permissions')
            ->where('slug', 'admin.reporttemplate.create')
            ->update(['slug' => 'admin.tipoinforme.create']);

        DB::table('permissions')
            ->where('slug', 'admin.reporttemplate.update')
            ->update(['slug' => 'admin.tipoinforme.update']);

        DB::table('permissions')
            ->where('slug', 'admin.reporttemplate.delete')
            ->update(['slug' => 'admin.tipoinforme.delete']);
    }
};

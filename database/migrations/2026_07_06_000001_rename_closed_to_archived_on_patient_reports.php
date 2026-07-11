<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('patient_reports', function (Blueprint $table) {
            $table->renameColumn('closed_at', 'archived_at');
        });

        DB::table('patient_reports')
            ->where('status', 'closed')
            ->update(['status' => 'archived']);

        DB::table('permissions')
            ->where('slug', 'report.close')
            ->update([
                'slug' => 'report.archive',
                'name' => 'Archivar informes',
                'action' => 'archive',
                'description' => 'Permite archivar y generar PDF de informes.',
            ]);
    }

    public function down(): void
    {
        DB::table('permissions')
            ->where('slug', 'report.archive')
            ->update([
                'slug' => 'report.close',
                'name' => 'Cerrar informes',
                'action' => 'close',
                'description' => 'Permite cerrar y generar PDF de informes.',
            ]);

        DB::table('patient_reports')
            ->where('status', 'archived')
            ->update(['status' => 'closed']);

        Schema::table('patient_reports', function (Blueprint $table) {
            $table->renameColumn('archived_at', 'closed_at');
        });
    }
};

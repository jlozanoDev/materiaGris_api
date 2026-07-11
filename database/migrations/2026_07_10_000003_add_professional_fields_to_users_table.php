<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('apellido')->nullable()->after('name');
            $table->string('num_colegiado')->nullable()->after('email');
            $table->string('especialidad')->nullable()->after('num_colegiado');
            $table->string('telefono')->nullable()->after('especialidad');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['apellido', 'num_colegiado', 'especialidad', 'telefono']);
        });
    }
};

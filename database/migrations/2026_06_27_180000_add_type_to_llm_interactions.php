<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('llm_interactions', function (Blueprint $table) {
            $table->string('type', 50)->default('extraction')->nullable()->after('patient_report_id');
        });
    }

    public function down(): void
    {
        Schema::table('llm_interactions', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};

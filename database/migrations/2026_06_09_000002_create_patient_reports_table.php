<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('patient_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('patient_id')->constrained('patients');
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('template_id')->nullable()->constrained('report_templates')->nullOnDelete();
            $table->string('status')->default('draft');
            $table->json('template_structure_snapshot');
            $table->json('values');
            $table->string('signature_path')->nullable();
            $table->string('pdf_path')->nullable();
            $table->timestamp('signed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index('patient_id');
            $table->index('user_id');
            $table->index('status');
            $table->index(['patient_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('patient_reports');
    }
};

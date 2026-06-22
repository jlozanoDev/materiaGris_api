<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create("llm_interactions", function (Blueprint $table) {
            $table->id();
            $table->foreignId("patient_report_id")->constrained("patient_reports")->cascadeOnDelete();
            $table->json("request_payload");
            $table->json("response_payload")->nullable();
            $table->integer("processing_time_ms")->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists("llm_interactions");
    }
};

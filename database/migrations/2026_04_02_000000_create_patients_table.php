<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('patients', function (Blueprint $table) {
            $table->id();
            $table->string('medical_record_number')->nullable()->unique();
            $table->string('national_id')->nullable()->unique();
            $table->string('first_name')->nullable()->index();
            $table->string('last_name')->nullable()->index();
            $table->string('second_last_name')->nullable()->index();
            $table->string('gender', 10)->nullable()->index();
            $table->date('date_of_birth')->nullable()->index();
            $table->string('city')->nullable()->index();
            $table->unsignedBigInteger('insurance_id')->nullable()->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_visit_at')->nullable()->index();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('patients');
    }
};

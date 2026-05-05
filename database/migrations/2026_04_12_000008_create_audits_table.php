<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAuditsTable extends Migration
{
    public function up(): void
    {
        Schema::create('audits', function (Blueprint $table) {
            $table->id();
            $table->string('type', 150);
            $table->string('module', 100)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();
            $table->string('actor_type', 100)->default('User');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('target_type', 100)->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('payload')->nullable();
            $table->json('meta')->nullable();
            $table->string('trace_id', 255)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['type']);
            $table->index(['module']);
            $table->index(['actor_id']);
            $table->index(['target_type', 'target_id']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
}

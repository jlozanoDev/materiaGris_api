<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePermissionsTable extends Migration
{
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('permission_categories')->nullOnDelete();
            $table->string('name', 100);
            $table->string('slug', 150)->unique();
            $table->string('action', 150)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('slug');
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
}

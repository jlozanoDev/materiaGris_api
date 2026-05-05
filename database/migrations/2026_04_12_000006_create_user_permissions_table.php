<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserPermissionsTable extends Migration
{
    public function up(): void
    {
        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->tinyInteger('grant')->default(1); // 1 = allow, -1 = deny
            $table->string('origin', 50)->default('user'); // 'role' or 'user'
            $table->unsignedBigInteger('origin_id')->nullable(); // id of role when origin='role'
            $table->foreignId('applied_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'permission_id']);
            $table->index('user_id');
            $table->index('permission_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_permissions');
    }
}

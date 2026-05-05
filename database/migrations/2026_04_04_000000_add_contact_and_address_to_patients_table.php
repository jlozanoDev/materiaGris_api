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
        Schema::table('patients', function (Blueprint $table) {
            // Contact
            $table->string('email')->nullable()->index();
            $table->string('phone')->nullable()->index();
            $table->string('mobile')->nullable()->index();
            $table->string('contact_name')->nullable();
            $table->string('contact_phone')->nullable()->index();

            // Address
            $table->string('address_line1')->nullable();
            $table->string('address_line2')->nullable();
            $table->string('neighborhood')->nullable()->index();
            $table->string('postal_code', 20)->nullable()->index();
            $table->string('state')->nullable()->index();
            $table->string('country')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('patients', function (Blueprint $table) {
            $table->dropColumn([
                'email',
                'phone',
                'mobile',
                'contact_name',
                'contact_phone',
                'address_line1',
                'address_line2',
                'neighborhood',
                'postal_code',
                'state',
                'country',
            ]);
        });
    }
};

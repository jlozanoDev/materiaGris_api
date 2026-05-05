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
        Schema::rename('direcciones', 'addresses');

        Schema::table('addresses', function (Blueprint $table) {
            $table->renameColumn('calle', 'street');
            $table->renameColumn('numero', 'number');
            $table->renameColumn('piso', 'floor');
            $table->renameColumn('puerta', 'door');
            $table->renameColumn('ciudad', 'city');
            $table->renameColumn('provincia', 'province');
            $table->renameColumn('codigo_postal', 'postal_code');
            $table->renameColumn('pais', 'country');
            $table->renameColumn('telefono_fijo', 'landline_phone');
            $table->renameColumn('movil_contacto', 'mobile_phone');
            $table->renameColumn('email_contacto', 'contact_email');
            $table->renameColumn('es_principal', 'is_primary');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('addresses', function (Blueprint $table) {
            $table->renameColumn('street', 'calle');
            $table->renameColumn('number', 'numero');
            $table->renameColumn('floor', 'piso');
            $table->renameColumn('door', 'puerta');
            $table->renameColumn('city', 'ciudad');
            $table->renameColumn('province', 'provincia');
            $table->renameColumn('postal_code', 'codigo_postal');
            $table->renameColumn('country', 'pais');
            $table->renameColumn('landline_phone', 'telefono_fijo');
            $table->renameColumn('mobile_phone', 'movil_contacto');
            $table->renameColumn('contact_email', 'email_contacto');
            $table->renameColumn('is_primary', 'es_principal');
        });

        Schema::rename('addresses', 'direcciones');
    }
};

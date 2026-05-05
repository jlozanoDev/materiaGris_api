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
        Schema::create('direcciones', function (Blueprint $table) {
            $table->id();

            // Relación con usuarios
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Datos de localización
            $table->string('alias')->nullable();
            $table->string('calle')->nullable();
            $table->string('numero')->nullable();
            $table->string('piso')->nullable();
            $table->string('puerta')->nullable();
            $table->string('ciudad')->nullable();
            $table->string('provincia')->nullable();
            $table->string('codigo_postal', 20)->nullable();
            $table->string('pais')->nullable();
            // Coordenadas (lat/lng)
            $table->decimal('lat', 10, 7)->nullable();
            $table->decimal('lng', 10, 7)->nullable();

            // Campos de contacto específicos por dirección
            $table->string('telefono_fijo', 20)->nullable();
            $table->string('movil_contacto', 20)->nullable();
            $table->string('email_contacto', 150)->nullable();

            // Flag para marcar dirección principal por usuario
            $table->boolean('es_principal')->default(false)->index();

            $table->timestamps();
            $table->softDeletes();

            // Índices recomendados
            $table->index(['user_id', 'es_principal']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direcciones');
    }
};

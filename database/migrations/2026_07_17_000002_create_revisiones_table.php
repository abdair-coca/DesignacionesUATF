<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('revisiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrera_id')->constrained('carreras')->cascadeOnDelete();
            $table->unsignedBigInteger('Id_gestion');
            $table->unsignedBigInteger('Id_periodo');
            $table->foreignId('solicitado_por')->constrained('users');
            $table->timestamp('solicitado_en')->nullable();
            $table->foreignId('revisado_por')->nullable()->constrained('users');
            $table->timestamp('revisado_en')->nullable();
            $table->string('estado', 20)->default('pendiente');
            $table->timestamps();

            $table->foreign('Id_gestion')->references('id')->on('gestiones');
            $table->foreign('Id_periodo')->references('id')->on('periodos');
            $table->unique(['carrera_id', 'Id_gestion', 'Id_periodo', 'estado'], 'revision_unica_pendiente');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('revisiones');
    }
};

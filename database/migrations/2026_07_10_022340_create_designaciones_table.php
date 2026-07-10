<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('Id_docente')->constrained('docentes')->cascadeOnDelete();
            $table->foreignId('Id_materia')->constrained('materias')->cascadeOnDelete();
            $table->foreignId('Id_grupo')->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('Id_gestion')->constrained('gestiones')->cascadeOnDelete();
            $table->foreignId('Id_periodo')->constrained('periodos')->cascadeOnDelete();
            $table->enum('estado', ['propuesta', 'aprobada', 'rechazada'])->default('propuesta');
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->unsignedBigInteger('aprobado_por')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designaciones');
    }
};

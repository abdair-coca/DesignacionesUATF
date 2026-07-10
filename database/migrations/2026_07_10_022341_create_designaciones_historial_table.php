<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designaciones_historial', function (Blueprint $table) {
            $table->id();
            $table->foreignId('designacion_id')->constrained('designaciones')->cascadeOnDelete();
            $table->string('campo');
            $table->string('valor_anterior')->nullable();
            $table->string('valor_nuevo')->nullable();
            $table->dateTime('fecha');
            $table->unsignedBigInteger('usuario_id')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designaciones_historial');
    }
};

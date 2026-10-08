<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('designacion_grupo_aperturas', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('materia_id');
            $table->unsignedSmallInteger('gestion');
            $table->unsignedInteger('numero');
            $table->timestamps();
            $table->unique(['materia_id', 'gestion', 'numero'], 'designacion_grupo_apertura_unica');
        });

        DB::statement(
            'CREATE VIEW v_designacion_grupos_abiertos AS '
            .'SELECT materia_id, gestion, MAX(numero) AS ultimo_grupo '
            .'FROM designacion_grupo_aperturas '
            .'GROUP BY materia_id, gestion',
        );
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS v_designacion_grupos_abiertos');
        Schema::dropIfExists('designacion_grupo_aperturas');
    }
};

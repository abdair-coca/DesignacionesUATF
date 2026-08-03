<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('propuestas', function (Blueprint $table) {
            $table->dropUnique('propuestas_carrera_gestion_periodo_unica');
        });
    }

    public function down(): void
    {
        Schema::table('propuestas', function (Blueprint $table) {
            $table->unique(
                ['carrera_id', 'gestion_id', 'periodo_id'],
                'propuestas_carrera_gestion_periodo_unica',
            );
        });
    }
};

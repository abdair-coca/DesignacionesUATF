<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('designaciones', function (Blueprint $table) {
            $table->index('Id_grupo');
            $table->index('Id_docente');
        });

        Schema::table('designaciones_historial', function (Blueprint $table) {
            $table->index(['designacion_id', 'fecha']);
        });
    }

    public function down(): void
    {
        Schema::table('designaciones_historial', function (Blueprint $table) {
            $table->dropIndex(['designacion_id', 'fecha']);
        });

        Schema::table('designaciones', function (Blueprint $table) {
            $table->dropIndex('Id_docente');
            $table->dropIndex('Id_grupo');
        });
    }
};

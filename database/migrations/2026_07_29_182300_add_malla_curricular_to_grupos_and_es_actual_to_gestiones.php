<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('grupos', function (Blueprint $table) {
            $table->foreignId('malla_curricular_id')
                ->nullable()
                ->constrained('malla_curricular')
                ->restrictOnDelete();
        });

        Schema::table('gestiones', function (Blueprint $table) {
            $table->boolean('es_actual')->default(false);
        });

        DB::statement('CREATE UNIQUE INDEX gestiones_es_actual_unica ON gestiones (es_actual) WHERE es_actual');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS gestiones_es_actual_unica');

        Schema::table('gestiones', function (Blueprint $table) {
            $table->dropColumn('es_actual');
        });

        Schema::table('grupos', function (Blueprint $table) {
            $table->dropForeign(['malla_curricular_id']);
            $table->dropColumn('malla_curricular_id');
        });
    }
};

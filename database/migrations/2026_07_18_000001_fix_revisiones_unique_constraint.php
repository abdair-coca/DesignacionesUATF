<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Drop old unique constraint que incluye estado
        Schema::table('revisiones', function ($table) {
            $table->dropUnique('revision_unica_pendiente');
        });

        // Partial unique index: solo una pendiente por carrera+gestion+periodo
        DB::statement('CREATE UNIQUE INDEX revision_unica_pendiente ON revisiones (carrera_id, "Id_gestion", "Id_periodo") WHERE estado = \'pendiente\'');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS revision_unica_pendiente');

        Schema::table('revisiones', function ($table) {
            $table->unique(['carrera_id', 'Id_gestion', 'Id_periodo', 'estado'], 'revision_unica_pendiente');
        });
    }
};

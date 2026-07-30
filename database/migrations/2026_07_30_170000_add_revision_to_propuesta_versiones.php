<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('propuesta_versiones', function (Blueprint $table) {
            $table->foreignId('revisado_por')->nullable()->after('retirado_en')->constrained('users')->restrictOnDelete();
            $table->timestamp('revisado_en')->nullable()->after('revisado_por');
            $table->text('observaciones')->nullable()->after('revisado_en');
        });

        Schema::create('propuesta_version_decisiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propuesta_version_designacion_id')->constrained('propuesta_version_designaciones')->restrictOnDelete();
            $table->string('decision', 30);
            $table->text('observacion')->nullable();
            $table->foreignId('decidido_por')->constrained('users')->restrictOnDelete();
            $table->timestamp('decidido_en');

            $table->unique('propuesta_version_designacion_id', 'decision_por_snapshot_unica');
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("CREATE OR REPLACE FUNCTION impedir_mutacion_decision_propuesta() RETURNS trigger AS $$ BEGIN RAISE EXCEPTION 'Las decisiones de revisión son inmutables'; END; $$ LANGUAGE plpgsql");
            DB::statement('CREATE TRIGGER propuesta_version_decisiones_inmutables BEFORE UPDATE OR DELETE ON propuesta_version_decisiones FOR EACH ROW EXECUTE FUNCTION impedir_mutacion_decision_propuesta()');
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('DROP TRIGGER IF EXISTS propuesta_version_decisiones_inmutables ON propuesta_version_decisiones');
            DB::statement('DROP FUNCTION IF EXISTS impedir_mutacion_decision_propuesta()');
        }

        Schema::dropIfExists('propuesta_version_decisiones');

        Schema::table('propuesta_versiones', function (Blueprint $table) {
            $table->dropForeign(['revisado_por']);
            $table->dropColumn(['revisado_por', 'revisado_en', 'observaciones']);
        });
    }
};

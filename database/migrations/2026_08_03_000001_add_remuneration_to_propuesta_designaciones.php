<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('propuesta_designaciones', function (Blueprint $table): void {
            $table->unsignedInteger('horas_pagadas')->default(0)->after('estado');
            $table->unsignedInteger('horas_no_pagadas')->default(0)->after('horas_pagadas');
            $table->text('observacion_remuneracion')->nullable()->after('horas_no_pagadas');
        });

        Schema::table('propuesta_version_designaciones', function (Blueprint $table): void {
            $table->unsignedInteger('horas_pagadas')->default(0)->after('materia_horas');
            $table->unsignedInteger('horas_no_pagadas')->default(0)->after('horas_pagadas');
            $table->text('observacion_remuneracion')->nullable()->after('horas_no_pagadas');
        });

        DB::statement('UPDATE propuesta_designaciones AS pd SET horas_pagadas = materias.horas, horas_no_pagadas = 0 FROM materias WHERE materias.id = pd.materia_id');

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE propuesta_version_designaciones DISABLE TRIGGER propuesta_version_designaciones_inmutables');
        }

        DB::statement('UPDATE propuesta_version_designaciones SET horas_pagadas = materia_horas, horas_no_pagadas = 0');

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE propuesta_version_designaciones ENABLE TRIGGER propuesta_version_designaciones_inmutables');
        }

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE propuesta_designaciones ADD CONSTRAINT propuesta_designaciones_horas_no_negativas CHECK (horas_pagadas >= 0 AND horas_no_pagadas >= 0)');
            DB::statement('ALTER TABLE propuesta_designaciones ADD CONSTRAINT propuesta_designaciones_horas_cubiertas CHECK (horas_pagadas + horas_no_pagadas >= 0)');
            DB::statement('ALTER TABLE propuesta_version_designaciones ADD CONSTRAINT propuesta_version_designaciones_horas_validas CHECK (horas_pagadas >= 0 AND horas_no_pagadas >= 0 AND horas_pagadas <= materia_horas AND horas_pagadas + horas_no_pagadas >= materia_horas)');
            DB::statement(<<<'SQL'
                CREATE OR REPLACE FUNCTION validar_horas_propuesta_designacion()
                RETURNS trigger AS $$
                DECLARE
                    horas_oficiales integer;
                BEGIN
                    SELECT horas INTO horas_oficiales FROM materias WHERE id = NEW.materia_id;

                    IF NEW.horas_pagadas > horas_oficiales THEN
                        RAISE EXCEPTION 'Las horas pagadas no pueden superar las horas oficiales de la materia';
                    END IF;

                    IF NEW.horas_pagadas + NEW.horas_no_pagadas < horas_oficiales THEN
                        RAISE EXCEPTION 'La distribucion debe cubrir las horas oficiales de la materia';
                    END IF;

                    RETURN NEW;
                END;
                $$ LANGUAGE plpgsql
            SQL);
            DB::statement('CREATE TRIGGER propuesta_designaciones_horas_validas BEFORE INSERT OR UPDATE OF materia_id, horas_pagadas, horas_no_pagadas ON propuesta_designaciones FOR EACH ROW EXECUTE FUNCTION validar_horas_propuesta_designacion()');
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('DROP TRIGGER IF EXISTS propuesta_designaciones_horas_validas ON propuesta_designaciones');
            DB::statement('DROP FUNCTION IF EXISTS validar_horas_propuesta_designacion()');
            DB::statement('ALTER TABLE propuesta_designaciones DROP CONSTRAINT IF EXISTS propuesta_designaciones_horas_no_negativas');
            DB::statement('ALTER TABLE propuesta_designaciones DROP CONSTRAINT IF EXISTS propuesta_designaciones_horas_cubiertas');
            DB::statement('ALTER TABLE propuesta_version_designaciones DROP CONSTRAINT IF EXISTS propuesta_version_designaciones_horas_validas');
        }

        Schema::table('propuesta_version_designaciones', function (Blueprint $table): void {
            $table->dropColumn(['horas_pagadas', 'horas_no_pagadas', 'observacion_remuneracion']);
        });

        Schema::table('propuesta_designaciones', function (Blueprint $table): void {
            $table->dropColumn(['horas_pagadas', 'horas_no_pagadas', 'observacion_remuneracion']);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const GRUPOS_REGISTRO = 'grupos_materia_legacy_registros';

    private const MATERIAS_REGISTRO = 'materias_carrera_legacy_registros';

    public function up(): void
    {
        Schema::create(self::GRUPOS_REGISTRO, function (Blueprint $table) {
            $table->foreignId('grupo_id')->primary()->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('materia_id')->constrained('materias')->restrictOnDelete();
            $table->timestamps();
        });

        Schema::create(self::MATERIAS_REGISTRO, function (Blueprint $table) {
            $table->foreignId('materia_id')->primary()->constrained('materias')->cascadeOnDelete();
            $table->foreignId('carrera_id')->constrained('carreras')->restrictOnDelete();
            $table->timestamps();
        });

        $ahora = now();
        DB::table(self::GRUPOS_REGISTRO)->insertUsing(
            ['grupo_id', 'materia_id', 'created_at', 'updated_at'],
            DB::table('grupos')->selectRaw('id, materia_id, ?, ?', [$ahora, $ahora]),
        );
        DB::table(self::MATERIAS_REGISTRO)->insertUsing(
            ['materia_id', 'carrera_id', 'created_at', 'updated_at'],
            DB::table('materias')->selectRaw('id, carrera_id, ?, ?', [$ahora, $ahora]),
        );

        Schema::table('grupos', function (Blueprint $table) {
            $table->dropForeign(['materia_id']);
            $table->dropColumn('materia_id');
        });

        Schema::table('materias', function (Blueprint $table) {
            $table->dropForeign(['carrera_id']);
            $table->dropColumn('carrera_id');
        });
    }

    public function down(): void
    {
        Schema::table('materias', function (Blueprint $table) {
            $table->foreignId('carrera_id')->nullable()->constrained('carreras')->restrictOnDelete();
        });
        Schema::table('grupos', function (Blueprint $table) {
            $table->foreignId('materia_id')->nullable()->constrained('materias')->restrictOnDelete();
        });

        DB::table(self::MATERIAS_REGISTRO)->orderBy('materia_id')->eachById(function (object $registro): void {
            DB::table('materias')->where('id', $registro->materia_id)->update(['carrera_id' => $registro->carrera_id]);
        }, 1000, 'materia_id', 'materia_id');
        DB::table(self::GRUPOS_REGISTRO)->orderBy('grupo_id')->eachById(function (object $registro): void {
            DB::table('grupos')->where('id', $registro->grupo_id)->update(['materia_id' => $registro->materia_id]);
        }, 1000, 'grupo_id', 'grupo_id');

        Schema::dropIfExists(self::GRUPOS_REGISTRO);
        Schema::dropIfExists(self::MATERIAS_REGISTRO);
    }
};

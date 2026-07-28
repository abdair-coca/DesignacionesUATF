<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('revisiones', function (Blueprint $table) {
            if (! Schema::hasColumn('revisiones', 'descripcion')) {
                $table->string('descripcion', 255)->nullable()->after('carrera_id');
            }
            if (! Schema::hasColumn('revisiones', 'observaciones')) {
                $table->text('observaciones')->nullable()->after('estado');
            }
        });
    }

    public function down(): void
    {
        Schema::table('revisiones', function (Blueprint $table) {
            if (Schema::hasColumn('revisiones', 'descripcion')) {
                $table->dropColumn('descripcion');
            }
            if (Schema::hasColumn('revisiones', 'observaciones')) {
                $table->dropColumn('observaciones');
            }
        });
    }
};

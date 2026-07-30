<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropuestaDesignacion extends Model
{
    protected $table = 'propuesta_designaciones';

    protected $fillable = [
        'propuesta_id',
        'docente_id',
        'materia_id',
        'grupo_id',
        'malla_curricular_id',
        'estado',
    ];

    public function propuesta(): BelongsTo
    {
        return $this->belongsTo(Propuesta::class);
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class);
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class);
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class);
    }

    public function mallaCurricular(): BelongsTo
    {
        return $this->belongsTo(MallaCurricular::class);
    }
}

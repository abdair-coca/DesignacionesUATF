<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class Grupo extends Model
{
    use HasFactory;

    protected $fillable = ['malla_curricular_id', 'codigo', 'estado'];

    public function materia(): HasOneThrough
    {
        return $this->hasOneThrough(
            Materia::class,
            MallaCurricular::class,
            'id',
            'id',
            'malla_curricular_id',
            'materia_id',
        );
    }

    public function mallaCurricular(): BelongsTo
    {
        return $this->belongsTo(MallaCurricular::class);
    }

    public function designaciones(): HasMany
    {
        return $this->hasMany(Designacion::class, 'Id_grupo');
    }
}

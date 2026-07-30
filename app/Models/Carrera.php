<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Carrera extends Model
{
    use HasFactory;

    protected $fillable = ['sigla', 'nombre'];

    public function materias(): HasManyThrough
    {
        return $this->hasManyThrough(
            Materia::class,
            MallaCurricular::class,
            'carrera_id',
            'id',
            'id',
            'materia_id',
        );
    }

    public function mallaCurricular(): HasMany
    {
        return $this->hasMany(MallaCurricular::class);
    }

    public function docentesOrigen(): HasMany
    {
        return $this->hasMany(Docente::class, 'carrera_origen_id');
    }
}

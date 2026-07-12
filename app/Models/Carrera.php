<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrera extends Model
{
    use HasFactory;

    protected $fillable = ['sigla', 'nombre'];

    public function materias(): HasMany
    {
        return $this->hasMany(Materia::class);
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

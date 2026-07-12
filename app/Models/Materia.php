<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Materia extends Model
{
    use HasFactory;

    protected $fillable = ['sigla', 'nombre', 'carrera_id', 'horas'];

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }

    public function mallaCurricular(): HasMany
    {
        return $this->hasMany(MallaCurricular::class);
    }

    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class);
    }

    public function designaciones(): HasMany
    {
        return $this->hasMany(Designacion::class, 'Id_materia');
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Docente extends Model
{
    protected $fillable = ['nombre', 'ci', 'carrera_origen_id'];

    public function carreraOrigen(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'carrera_origen_id');
    }

    public function designaciones(): HasMany
    {
        return $this->hasMany(Designacion::class, 'Id_docente');
    }
}

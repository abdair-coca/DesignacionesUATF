<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Grupo extends Model
{
    use HasFactory;

    protected $fillable = ['materia_id', 'codigo', 'estado'];

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class);
    }

    public function designaciones(): HasMany
    {
        return $this->hasMany(Designacion::class, 'Id_grupo');
    }
}

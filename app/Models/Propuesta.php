<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Propuesta extends Model
{
    use HasFactory;

    protected $fillable = [
        'carrera_id',
        'gestion_id',
        'periodo_id',
        'creado_por',
        'descripcion',
        'estado',
    ];

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }

    public function gestion(): BelongsTo
    {
        return $this->belongsTo(Gestion::class);
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class);
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function designaciones(): HasMany
    {
        return $this->hasMany(PropuestaDesignacion::class);
    }

    public function versiones(): HasMany
    {
        return $this->hasMany(PropuestaVersion::class);
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(PropuestaEvento::class);
    }
}

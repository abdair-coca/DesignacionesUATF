<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Designacion extends Model
{
    use HasFactory;

    protected $table = 'designaciones';

    protected $fillable = [
        'Id_docente',
        'Id_materia',
        'Id_grupo',
        'Id_gestion',
        'Id_periodo',
        'estado',
        'creado_por',
        'aprobado_por',
    ];

    protected function casts(): array
    {
        return [
            'estado' => 'string',
        ];
    }

    public function scopeActivas(Builder $query): Builder
    {
        return $query->where('estado', '!=', 'rechazada');
    }

    public function scopeForGestionPeriodo(Builder $query, int $gestionId, int $periodoId): Builder
    {
        return $query->where('Id_gestion', $gestionId)
            ->where('Id_periodo', $periodoId);
    }

    public function docente(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'Id_docente');
    }

    public function materia(): BelongsTo
    {
        return $this->belongsTo(Materia::class, 'Id_materia');
    }

    public function grupo(): BelongsTo
    {
        return $this->belongsTo(Grupo::class, 'Id_grupo');
    }

    public function gestion(): BelongsTo
    {
        return $this->belongsTo(Gestion::class, 'Id_gestion');
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class, 'Id_periodo');
    }

    public function historial(): HasMany
    {
        return $this->hasMany(DesignacionHistorial::class, 'designacion_id');
    }

    public function creador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'creado_por');
    }

    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }
}

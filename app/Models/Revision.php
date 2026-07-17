<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Revision extends Model
{
    protected $table = 'revisiones';

    protected $fillable = [
        'carrera_id',
        'Id_gestion',
        'Id_periodo',
        'solicitado_por',
        'solicitado_en',
        'revisado_por',
        'revisado_en',
        'estado',
    ];

    protected function casts(): array
    {
        return [
            'solicitado_en' => 'datetime',
            'revisado_en' => 'datetime',
        ];
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class, 'carrera_id');
    }

    public function solicitante(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }

    public function gestion(): BelongsTo
    {
        return $this->belongsTo(Gestion::class, 'Id_gestion');
    }

    public function periodo(): BelongsTo
    {
        return $this->belongsTo(Periodo::class, 'Id_periodo');
    }
}

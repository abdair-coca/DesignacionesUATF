<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PropuestaVersion extends Model
{
    protected $table = 'propuesta_versiones';

    protected $fillable = [
        'propuesta_id',
        'numero',
        'estado',
        'enviado_por',
        'enviado_en',
        'retirado_por',
        'retirado_en',
    ];

    protected function casts(): array
    {
        return [
            'enviado_en' => 'datetime',
            'retirado_en' => 'datetime',
        ];
    }

    public function propuesta(): BelongsTo
    {
        return $this->belongsTo(Propuesta::class);
    }

    public function remitente(): BelongsTo
    {
        return $this->belongsTo(User::class, 'enviado_por');
    }

    public function retiroUsuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'retirado_por');
    }

    public function designaciones(): HasMany
    {
        return $this->hasMany(PropuestaVersionDesignacion::class);
    }
}

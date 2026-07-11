<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DesignacionHistorial extends Model
{
    protected $table = 'designaciones_historial';

    protected $fillable = [
        'designacion_id',
        'campo',
        'valor_anterior',
        'valor_nuevo',
        'fecha',
        'usuario_id',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'datetime',
        ];
    }

    public function designacion(): BelongsTo
    {
        return $this->belongsTo(Designacion::class, 'designacion_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }
}

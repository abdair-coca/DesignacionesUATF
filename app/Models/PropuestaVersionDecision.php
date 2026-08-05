<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropuestaVersionDecision extends Model
{
    use HasFactory;

    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    protected $table = 'propuesta_version_decisiones';

    protected $fillable = [
        'propuesta_version_designacion_id',
        'decision',
        'observacion',
        'decidido_por',
        'decidido_en',
    ];

    protected function casts(): array
    {
        return [
            'decidido_en' => 'datetime',
        ];
    }

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(PropuestaVersionDesignacion::class, 'propuesta_version_designacion_id');
    }

    public function revisor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decidido_por');
    }
}

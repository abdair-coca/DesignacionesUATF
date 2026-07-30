<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropuestaEvento extends Model
{
    public const CREATED_AT = null;

    public const UPDATED_AT = null;

    protected $table = 'propuesta_eventos';

    protected $fillable = [
        'propuesta_id',
        'propuesta_version_id',
        'usuario_id',
        'tipo',
        'datos',
        'ocurrio_en',
    ];

    protected function casts(): array
    {
        return [
            'datos' => 'array',
            'ocurrio_en' => 'datetime',
        ];
    }

    public function propuesta(): BelongsTo
    {
        return $this->belongsTo(Propuesta::class);
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(PropuestaVersion::class, 'propuesta_version_id');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

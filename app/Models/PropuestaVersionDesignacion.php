<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PropuestaVersionDesignacion extends Model
{
    protected $table = 'propuesta_version_designaciones';

    protected $guarded = [];

    public function version(): BelongsTo
    {
        return $this->belongsTo(PropuestaVersion::class, 'propuesta_version_id');
    }

    public function decision(): HasOne
    {
        return $this->hasOne(PropuestaVersionDecision::class, 'propuesta_version_designacion_id');
    }
}

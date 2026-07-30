<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropuestaVersionDesignacion extends Model
{
    protected $table = 'propuesta_version_designaciones';

    protected $guarded = [];

    public function version(): BelongsTo
    {
        return $this->belongsTo(PropuestaVersion::class, 'propuesta_version_id');
    }
}

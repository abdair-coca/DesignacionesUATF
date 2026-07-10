<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Periodo extends Model
{
    protected $fillable = ['nombre'];

    public function designaciones(): HasMany
    {
        return $this->hasMany(Designacion::class, 'Id_periodo');
    }
}

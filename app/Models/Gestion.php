<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gestion extends Model
{
    protected $table = 'gestiones';

    protected $fillable = ['nombre'];

    public function designaciones(): HasMany
    {
        return $this->hasMany(Designacion::class, 'Id_gestion');
    }
}

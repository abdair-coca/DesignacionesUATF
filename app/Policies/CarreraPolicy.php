<?php

namespace App\Policies;

use App\Models\Carrera;
use App\Models\User;

class CarreraPolicy
{
    public function view(User $user, Carrera $carrera): bool
    {
        return $user->administraCarrera($carrera->id);
    }

    public function manage(User $user, Carrera $carrera): bool
    {
        return $user->administraCarrera($carrera->id);
    }
}

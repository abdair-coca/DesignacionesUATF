<?php

namespace App\Policies;

use App\Models\Propuesta;
use App\Models\User;

class PropuestaPolicy
{
    public function view(User $user, Propuesta $propuesta): bool
    {
        return $user->administraCarrera($propuesta->carrera_id);
    }

    public function update(User $user, Propuesta $propuesta): bool
    {
        return $this->view($user, $propuesta)
            && $propuesta->estado === 'borrador'
            && ! $propuesta->versiones()->where('estado', 'pendiente')->exists();
    }

    public function send(User $user, Propuesta $propuesta): bool
    {
        return $this->update($user, $propuesta);
    }
}

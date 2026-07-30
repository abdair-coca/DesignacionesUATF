<?php

namespace App\Policies;

use App\Models\Carrera;
use App\Models\Designacion;
use App\Models\User;

class DesignacionPolicy
{
    public function create(User $user, Carrera $carrera): bool
    {
        return $user->administraCarrera($carrera->id);
    }

    public function view(User $user, Designacion $designacion): bool
    {
        return $user->administraCarrera((int) $designacion->mallaCurricular?->carrera_id);
    }

    public function update(User $user, Designacion $designacion): bool
    {
        return $this->view($user, $designacion) && $designacion->estado === 'propuesta';
    }

    public function delete(User $user, Designacion $designacion): bool
    {
        return $this->update($user, $designacion);
    }
}

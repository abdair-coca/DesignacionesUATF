<?php

namespace App\Policies;

use App\Models\PropuestaVersion;
use App\Models\User;

class PropuestaVersionPolicy
{
    public function view(User $user, PropuestaVersion $version): bool
    {
        return $user->esVicerrectorado()
            && in_array($version->estado, ['pendiente', 'retirada', 'observada', 'aprobada'], true);
    }

    public function review(User $user, PropuestaVersion $version): bool
    {
        return $user->esVicerrectorado() && $version->estado === 'pendiente';
    }

    public function withdraw(User $user, PropuestaVersion $version): bool
    {
        $propuesta = $version->propuesta;

        return $version->estado === 'pendiente'
            && $version->enviado_por === $user->id
            && $propuesta
            && $user->administraCarrera($propuesta->carrera_id);
    }
}

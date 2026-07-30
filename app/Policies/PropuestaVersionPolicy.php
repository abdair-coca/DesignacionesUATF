<?php

namespace App\Policies;

use App\Models\PropuestaVersion;
use App\Models\User;

class PropuestaVersionPolicy
{
    public function withdraw(User $user, PropuestaVersion $version): bool
    {
        $propuesta = $version->propuesta;

        return $version->estado === 'pendiente'
            && $version->enviado_por === $user->id
            && $propuesta
            && $user->administraCarrera($propuesta->carrera_id);
    }
}

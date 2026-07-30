<?php

namespace App\Policies;

use App\Models\Carrera;
use App\Models\Revision;
use App\Models\User;

class RevisionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->esVicerrectorado();
    }

    public function create(User $user, Carrera $carrera): bool
    {
        return $user->administraCarrera($carrera->id);
    }

    public function submit(User $user, Revision $revision): bool
    {
        return $user->administraCarrera($revision->carrera_id)
            && $revision->solicitado_por === $user->id
            && ! in_array($revision->estado, ['pendiente', 'revisado'], true);
    }

    public function withdraw(User $user, Revision $revision): bool
    {
        return $user->administraCarrera($revision->carrera_id)
            && $revision->solicitado_por === $user->id
            && $revision->estado === 'pendiente';
    }

    public function delete(User $user, Revision $revision): bool
    {
        return $user->administraCarrera($revision->carrera_id)
            && $revision->solicitado_por === $user->id
            && $revision->estado !== 'revisado';
    }

    public function review(User $user, Revision $revision): bool
    {
        return $user->esVicerrectorado() && $revision->estado === 'pendiente';
    }
}

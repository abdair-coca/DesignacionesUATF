<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'rol', 'carrera_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROL_DIRECTOR_CARRERA = 'director_carrera';

    public const ROL_VICERRECTORADO = 'vicerrectorado';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'carrera_id' => 'integer',
        ];
    }

    public function esDirectorCarrera(): bool
    {
        return $this->rol === self::ROL_DIRECTOR_CARRERA;
    }

    public function esVicerrectorado(): bool
    {
        return $this->rol === self::ROL_VICERRECTORADO;
    }

    public function administraCarrera(int $carreraId): bool
    {
        return $this->esDirectorCarrera() && (int) $this->carrera_id === $carreraId;
    }

    public function carrera(): BelongsTo
    {
        return $this->belongsTo(Carrera::class);
    }
}

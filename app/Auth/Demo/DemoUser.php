<?php

namespace App\Auth\Demo;

use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Usuario temporal que conserva el contrato del modelo real sin persistirse.
 */
class DemoUser extends User
{
    public $incrementing = false;

    protected $keyType = 'string';

    public function getKeyName(): string
    {
        return 'demo_id';
    }

    public function getAuthIdentifierName(): string
    {
        return 'demo_id';
    }

    public function isDemo(): bool
    {
        return true;
    }

    /**
     * Jachasun no contiene todavía las tablas de notificaciones locales.
     * Este objeto vacío permite renderizar el layout sin abrir una consulta.
     */
    public function unreadNotifications(): object
    {
        return new class
        {
            public function count(): int
            {
                return 0;
            }

            public function latest(): self
            {
                return $this;
            }

            public function limit(int $limit): self
            {
                return $this;
            }

            public function get(): Collection
            {
                return collect();
            }

            public function update(array $values): int
            {
                return 0;
            }
        };
    }

    public function notify($instance): static
    {
        return $this;
    }
}

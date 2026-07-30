<?php

namespace App\Notifications;

use App\Models\PropuestaVersion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PropuestaActualizadaNotification extends Notification
{
    use Queueable;

    public function __construct(
        private PropuestaVersion $version,
        private string $evento,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $propuesta = $this->version->propuesta;

        return [
            'evento' => $this->evento,
            'propuesta_id' => $this->version->propuesta_id,
            'version_id' => $this->version->id,
            'titulo' => $this->titulo(),
            'detalle' => "{$propuesta->carrera->nombre} - {$propuesta->gestion->nombre}/{$propuesta->periodo->nombre}",
            'url' => $notifiable->esVicerrectorado()
                ? route('versiones.revisar', $this->version)
                : route('propuestas.editar', $propuesta),
        ];
    }

    private function titulo(): string
    {
        return match ($this->evento) {
            'enviada' => "Version {$this->version->numero} enviada a revision",
            'reenviada' => "Version {$this->version->numero} reenviada a revision",
            'retirada' => "Version {$this->version->numero} retirada por su Director",
            'observada' => "Version {$this->version->numero} tiene observaciones",
            'aprobacion_parcial' => "Version {$this->version->numero} tiene filas aprobadas",
            'aprobada_final' => "Version {$this->version->numero} fue aprobada oficialmente",
            default => "Actualizacion de la version {$this->version->numero}",
        };
    }
}

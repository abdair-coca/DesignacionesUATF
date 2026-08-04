<?php

namespace App\Notifications;

use App\Models\PropuestaVersion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Ramsey\Uuid\Uuid;

class PropuestaActualizadaNotification extends Notification
{
    use Queueable;

    public function __construct(
        private PropuestaVersion $version,
        private string $evento,
        private array $resumen = [],
    ) {}

    public function via(object $notifiable): array
    {
        $this->id = Uuid::uuid5(
            Uuid::NAMESPACE_URL,
            implode('|', [
                static::class,
                $notifiable::class,
                (string) $notifiable->getKey(),
                (string) $this->version->getKey(),
                $this->evento,
            ]),
        )->toString();

        return ['database'];
    }

    public function shouldSend(object $notifiable, string $channel): bool
    {
        return $channel !== 'database'
            || ! $notifiable->notifications()->whereKey($this->id)->exists();
    }

    public function toArray(object $notifiable): array
    {
        $propuesta = $this->version->propuesta;
        $contexto = "{$propuesta->carrera->nombre} - {$propuesta->gestion->nombre}/{$propuesta->periodo->nombre}";
        $descripcion = trim((string) $propuesta->descripcion);

        return [
            'evento' => $this->evento,
            'propuesta_id' => $this->version->propuesta_id,
            'version_id' => $this->version->id,
            'titulo' => $this->titulo(),
            'detalle' => $descripcion !== '' ? "{$descripcion} - {$contexto}" : $contexto,
            'resumen' => $this->resumen,
            'url' => $notifiable->esVicerrectorado()
                ? route('revisiones.revisar', $this->version)
                : route('designaciones.editar', $propuesta),
        ];
    }

    private function titulo(): string
    {
        return match ($this->evento) {
            'enviada' => "Version {$this->version->numero} enviada a revision",
            'reenviada' => "Version {$this->version->numero} reenviada a revision",
            'retirada' => "Version {$this->version->numero} retirada por su Director",
            'observada' => $this->tituloObservada(),
            'aprobacion_parcial' => "Version {$this->version->numero} tiene filas aprobadas",
            'aprobada_final' => "Version {$this->version->numero} fue aprobada oficialmente",
            default => "Actualizacion de la version {$this->version->numero}",
        };
    }

    private function tituloObservada(): string
    {
        $filasAprobadas = (int) ($this->resumen['filas_aprobadas'] ?? 0);

        return $filasAprobadas > 0
            ? "Version {$this->version->numero} tiene observaciones y filas aprobadas"
            : "Version {$this->version->numero} tiene observaciones";
    }
}

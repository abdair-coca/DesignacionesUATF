<?php

namespace App\Data\Designaciones;

final readonly class DetalleAsignacion
{
    public function __construct(
        public int $id,
        public ?int $docenteId,
        public ?string $ci,
        public ?string $docenteNombre,
        public ?int $materiaId,
        public ?string $materiaSigla,
        public ?string $materiaNombre,
        public ?int $grupoId,
        public ?int $horasTeoricas,
        public ?int $horasPracticas,
        public ?int $horasLaboratorio,
    ) {}

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'docente_id' => $this->docenteId,
            'ci' => $this->ci,
            'docente_nombre' => $this->docenteNombre,
            'materia_id' => $this->materiaId,
            'materia_sigla' => $this->materiaSigla,
            'materia_nombre' => $this->materiaNombre,
            'grupo_id' => $this->grupoId,
            'horas_teoricas' => $this->horasTeoricas,
            'horas_practicas' => $this->horasPracticas,
            'horas_laboratorio' => $this->horasLaboratorio,
        ];
    }
}

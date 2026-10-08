<?php

namespace App\Data\Designaciones;

final readonly class OfertaMateria
{
    /**
     * @param  array<int, int>  $grupos
     */
    public function __construct(
        public int $id,
        public string $sigla,
        public string $nombre,
        public ?int $nivelAcademico,
        public ?int $mencionId,
        public int $horasTeoricas,
        public int $horasPracticas,
        public int $horasLaboratorio,
        public array $grupos,
        public ?int $grupoSiguiente,
        public bool $gruposDisponibles,
    ) {}

    /** @return array<string, int|string|null|array<int, int>|bool> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'sigla' => $this->sigla,
            'nombre' => $this->nombre,
            'nivel_academico' => $this->nivelAcademico,
            'mencion_id' => $this->mencionId,
            'horas_teoricas' => $this->horasTeoricas,
            'horas_practicas' => $this->horasPracticas,
            'horas_laboratorio' => $this->horasLaboratorio,
            'grupos' => $this->grupos,
            'grupo_siguiente' => $this->grupoSiguiente,
            'grupos_disponibles' => $this->gruposDisponibles,
        ];
    }
}

<?php

namespace App\Services\Jachasun;

use App\Data\Designaciones\DecisionAsignacion;
use App\Data\Designaciones\DesignacionCabecera;
use App\Data\Designaciones\DesignacionResumen;
use App\Data\Designaciones\DetalleAsignacion;
use App\Data\Designaciones\DocenteResumen;
use App\Data\Designaciones\OfertaMateria;
use App\Data\Designaciones\RevisionDesignacion;
use App\Exceptions\InvalidDesignacionesResponse;

final class DesignacionesResponseMapper
{
    /** @param object|array<string, mixed> $fila */
    public function designacionResumen(object|array $fila): DesignacionResumen
    {
        return new DesignacionResumen(
            id: $this->integer($this->field($fila, 'r_id'), 1),
            programaCodigo: $this->requiredText($this->field($fila, 'r_id_programa')),
            programaNombre: $this->requiredText($this->field($fila, 'r_programa')),
            detalle: $this->requiredText($this->field($fila, 'r_detalle')),
            fecha: $this->date($this->field($fila, 'r_fecha')),
            gestion: $this->numericText($this->field($fila, 'r_id_gestion')),
            periodo: $this->numericText($this->field($fila, 'r_id_periodo')),
            observacion: $this->nullableText($this->field($fila, 'r_obs')),
            estado: $this->nullableText($this->field($fila, 'r_estado')),
        );
    }

    /** @param object|array<string, mixed> $fila */
    public function designacionCabecera(object|array $fila): DesignacionCabecera
    {
        return new DesignacionCabecera(
            fecha: $this->date($this->field($fila, 'fecha')),
            programaCodigo: $this->requiredText($this->field($fila, 'id_programa')),
            programaNombre: $this->requiredText($this->field($fila, 'programa')),
            gestion: $this->numericText($this->field($fila, 'id_gestion')),
            periodo: $this->numericText($this->field($fila, 'id_periodo')),
            observacion: $this->nullableText($this->field($fila, 'obs')),
        );
    }

    /** @param object|array<string, mixed> $fila */
    public function detalleAsignacion(object|array $fila): DetalleAsignacion
    {
        $escritura = $this->hasField($fila, 'r_id_detalle');

        return new DetalleAsignacion(
            id: $this->integer($this->field($fila, 'r_id'), 1),
            docenteId: $this->nullableInteger($this->field($fila, 'r_id_docente')),
            ci: $this->nullableText($escritura ? $this->optionalField($fila, 'r_ci') : $this->field($fila, 'r_ci')),
            docenteNombre: $this->nullableText($escritura
                ? ($this->optionalField($fila, 'r_nombres') ?? $this->optionalField($fila, 'r_docente'))
                : $this->field($fila, 'r_nombres')),
            materiaId: $this->nullableInteger($this->field($fila, 'r_id_materia')),
            materiaSigla: $this->nullableText($escritura
                ? $this->optionalField($fila, 'r_sigla')
                : $this->field($fila, 'r_sigla')),
            materiaNombre: $this->nullableText($this->field($fila, 'r_materia')),
            grupoId: $this->nullableInteger($this->field($fila, 'r_id_grupo')),
            horasTeoricas: $this->nullableInteger($this->field($fila, 'r_hrs_teoricas')),
            horasPracticas: $this->nullableInteger($this->field($fila, 'r_hrs_practicas')),
            horasLaboratorio: $this->nullableInteger($this->field($fila, 'r_hrs_laboratorio')),
        );
    }

    /** @param object|array<string, mixed> $fila */
    public function docenteResumen(object|array $fila): ?DocenteResumen
    {
        if ($this->hasField($fila, 'r_id_docente')) {
            $nombre = implode(' ', array_filter([
                $this->nullableText($this->field($fila, 'r_paterno')),
                $this->nullableText($this->field($fila, 'r_materno')),
                $this->nullableText($this->field($fila, 'r_nombres')),
            ], fn (?string $parte): bool => $parte !== null && $parte !== ''));
            $id = $this->field($fila, 'r_id_docente');
            $ci = $this->field($fila, 'r_ci');
            $programaCodigo = $this->nullableText($this->optionalField($fila, 'r_id_programa'));
            $programaNombre = $this->nullableText($this->optionalField($fila, 'r_programa'));
        } else {
            $nombre = $this->requiredText($this->field($fila, 'nombre'));
            $id = $this->field($fila, 'id');
            $ci = $this->field($fila, 'ci');
            $programaCodigo = null;
            $programaNombre = null;
        }

        if ($id === null) {
            return null;
        }

        return new DocenteResumen(
            id: $this->integer($id, 0),
            nombre: $nombre,
            ci: $this->nullableText($ci),
            programaCodigo: $programaCodigo,
            programaNombre: $programaNombre,
        );
    }

    /**
     * El caller entrega los grupos autorizados ya reunidos por materia; su
     * selección permanece en la lógica vigente del servicio.
     *
     * @param  object|array<string, mixed>  $fila
     * @param  array<int, mixed>  $grupos
     */
    public function ofertaMateria(
        object|array $fila,
        array $grupos,
        ?int $grupoSiguiente,
        bool $gruposDisponibles,
    ): OfertaMateria {
        $gruposMapeados = array_map(fn (mixed $grupo): int => $this->integer($grupo, 1), $grupos);

        return new OfertaMateria(
            id: $this->integer($this->field($fila, 'r_id_materia'), 1),
            sigla: $this->requiredText($this->field($fila, 'r_sigla')),
            nombre: $this->requiredText($this->field($fila, 'r_materia')),
            nivelAcademico: $this->nullableInteger($this->field($fila, 'r_nivel_academico')),
            mencionId: $this->nullableInteger($this->field($fila, 'r_id_mencion')),
            horasTeoricas: $this->nullableInteger($this->field($fila, 'hrs_teoricas')) ?? 0,
            horasPracticas: $this->nullableInteger($this->field($fila, 'hrs_practicas')) ?? 0,
            horasLaboratorio: $this->nullableInteger($this->field($fila, 'hrs_laboratorio')) ?? 0,
            grupos: $gruposMapeados,
            grupoSiguiente: $grupoSiguiente === null ? null : $this->integer($grupoSiguiente, 1),
            gruposDisponibles: $gruposDisponibles,
        );
    }

    /** @param object|array<string, mixed> $fila */
    public function decisionAsignacion(object|array $fila): DecisionAsignacion
    {
        return new DecisionAsignacion(
            idDetalle: $this->integer($this->field($fila, 'r_id_detalle'), 1),
            estado: $this->nullableText($this->field($fila, 'r_estado')),
            observacion: $this->nullableText($this->field($fila, 'r_obs')),
        );
    }

    /** @param object|array<string, mixed> $fila */
    public function revisionDesignacion(object|array $fila): RevisionDesignacion
    {
        return new RevisionDesignacion(
            estado: $this->nullableText($this->field($fila, 'r_estado')),
            observacion: $this->nullableText($this->field($fila, 'r_obs_vicerrectorado')),
        );
    }

    private function hasField(object|array $fila, string $campo): bool
    {
        return is_array($fila) ? array_key_exists($campo, $fila) : property_exists($fila, $campo);
    }

    private function field(object|array $fila, string $campo): mixed
    {
        if (! $this->hasField($fila, $campo)) {
            throw new InvalidDesignacionesResponse;
        }

        return is_array($fila) ? $fila[$campo] : $fila->{$campo};
    }

    private function optionalField(object|array $fila, string $campo): mixed
    {
        return $this->hasField($fila, $campo) ? $this->field($fila, $campo) : null;
    }

    private function requiredText(mixed $valor): string
    {
        if (! is_string($valor) || trim($valor) === '') {
            throw new InvalidDesignacionesResponse;
        }

        return trim($valor);
    }

    private function nullableText(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        if (! is_string($valor)) {
            throw new InvalidDesignacionesResponse;
        }

        return trim($valor);
    }

    private function numericText(mixed $valor): string
    {
        if (is_int($valor)) {
            if ($valor < 0) {
                throw new InvalidDesignacionesResponse;
            }

            return (string) $valor;
        }

        if (! is_string($valor) || preg_match('/^\d+$/D', trim($valor)) !== 1) {
            throw new InvalidDesignacionesResponse;
        }

        return trim($valor);
    }

    private function nullableInteger(mixed $valor): ?int
    {
        return $valor === null ? null : $this->integer($valor, 0);
    }

    private function integer(mixed $valor, int $minimo): int
    {
        if (is_int($valor)) {
            $entero = $valor;
        } elseif (is_string($valor) && preg_match('/^\d+$/D', trim($valor)) === 1) {
            $digitos = ltrim(trim($valor), '0');
            $digitos = $digitos === '' ? '0' : $digitos;
            $maximo = (string) PHP_INT_MAX;

            if (strlen($digitos) > strlen($maximo)
                || (strlen($digitos) === strlen($maximo) && strcmp($digitos, $maximo) > 0)) {
                throw new InvalidDesignacionesResponse;
            }

            $entero = (int) $digitos;
        } else {
            throw new InvalidDesignacionesResponse;
        }

        if ($entero < $minimo) {
            throw new InvalidDesignacionesResponse;
        }

        return $entero;
    }

    private function date(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        if (! is_string($valor)) {
            throw new InvalidDesignacionesResponse;
        }

        $fecha = trim($valor);

        if (preg_match(
            '/^(\d{4})-(\d{2})-(\d{2})(?:[ T](\d{2}):(\d{2}):(\d{2})(?:\.(\d{1,6}))?)?$/D',
            $fecha,
            $partes,
        ) !== 1) {
            throw new InvalidDesignacionesResponse;
        }

        if (! checkdate((int) $partes[2], (int) $partes[3], (int) $partes[1])
            || (isset($partes[4]) && (int) $partes[4] > 23)
            || (isset($partes[5]) && (int) $partes[5] > 59)
            || (isset($partes[6]) && (int) $partes[6] > 59)) {
            throw new InvalidDesignacionesResponse;
        }

        return $fecha;
    }
}

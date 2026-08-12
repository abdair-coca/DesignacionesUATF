<?php

namespace App\Services\Institutional;

use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use LogicException;

class InstitutionalDesignacionesService
{
    public function __construct(private DatabaseManager $database) {}

    /**
     * Consulta el catÃ¡logo institucional mediante la Ãºnica funciÃ³n autorizada.
     *
     * @return Collection<int, array{
     *     id: int,
     *     programa_codigo: string,
     *     programa_nombre: string,
     *     detalle: string,
     *     fecha: string|null,
     *     gestion: string,
     *     periodo: string,
     *     observacion: string|null,
     *     estado: string|null
     * }>
     */
    public function listar(string $programa, string|int $gestion, string|int $periodo): Collection
    {
        if (! config('institutional.enabled')) {
            throw new LogicException('La integraciÃ³n institucional estÃ¡ deshabilitada.');
        }

        [$programa, $gestion, $periodo] = $this->validarParametros($programa, $gestion, $periodo);
        $connection = $this->database->connection(config('institutional.connection', 'institutional'));

        return $connection->transaction(function () use ($connection, $programa, $gestion, $periodo): Collection {
            // Impide que una eventual implementaciÃ³n futura de la funciÃ³n escriba en esta sesiÃ³n.
            $connection->statement('SET TRANSACTION READ ONLY');

            return collect($connection->select(
                'SELECT * FROM designaciones.f_asignaciones(?, ?, ?)',
                [$programa, $gestion, $periodo],
            ))->map(fn (object|array $fila): array => $this->normalizarFila($fila))->values();
        });
    }

    /**
     * @return array{string, string, string}
     */
    private function validarParametros(string $programa, string|int $gestion, string|int $periodo): array
    {
        $programa = strtoupper(trim($programa));
        $gestion = trim((string) $gestion);
        $periodo = trim((string) $periodo);

        if (! preg_match('/^[A-Z0-9_-]{2,20}$/', $programa)) {
            throw new InvalidArgumentException('El cÃ³digo de programa no es vÃ¡lido.');
        }

        if (! preg_match('/^(?:0|\d{4})$/', $gestion)) {
            throw new InvalidArgumentException('La gestiÃ³n debe tener cuatro dÃ­gitos o ser 0.');
        }

        if (! preg_match('/^(?:0|\d{1,2})$/', $periodo)) {
            throw new InvalidArgumentException('El periodo debe ser numÃ©rico o ser 0.');
        }

        return [$programa, $gestion, $periodo];
    }

    /**
     * @param  object|array<string, mixed>  $fila
     * @return array<string, int|string|null>
     */
    private function normalizarFila(object|array $fila): array
    {
        $valor = static fn (string $campo): mixed => is_array($fila)
            ? ($fila[$campo] ?? null)
            : ($fila->{$campo} ?? null);
        $texto = static function (mixed $valor): ?string {
            if ($valor === null) {
                return null;
            }

            return trim((string) $valor);
        };

        return [
            'id' => (int) $valor('r_id'),
            'programa_codigo' => (string) $valor('r_id_programa'),
            'programa_nombre' => trim((string) $valor('r_programa')),
            'detalle' => trim((string) $valor('r_detalle')),
            'fecha' => $texto($valor('r_fecha')),
            'gestion' => (string) $valor('r_id_gestion'),
            'periodo' => (string) $valor('r_id_periodo'),
            'observacion' => $texto($valor('r_obs')),
            'estado' => $texto($valor('r_estado')),
        ];
    }
}

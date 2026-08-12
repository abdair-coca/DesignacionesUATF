# Datasets de testing

## Seguridad obligatoria

- Solo ejecutar con `--env=testing` o en entorno local explícitamente autorizado.
- Los seeders comprueban `APP_ENV`, host, puerto, nombre de base y marcadores de producción antes de modificar datos.
- `production`, host/base con indicadores `prod`, `production`, `live` o `real`, y destinos ambiguos son rechazados.
- No usar `DatabaseSeeder` para estos perfiles: contiene datos demo con dominios institucionales.
- Usuarios son sintéticos. Credencial fixture: `testing-password`. No es credencial institucional ni secreto de producción.
- No se insertan filas deliberadamente inválidas; esas expectativas quedan para pruebas posteriores.

## Perfiles

| Perfil | Objetivo | Usuarios | Carreras | Docentes | Grupos | Designaciones legado | Propuestas | Tiempo observado | Memoria pico |
| --- | --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| `small` | Desarrollo/manual/feature | 5 | 4 | 24 | 48 | 432 | 5 | 1,12 s | 32 MB |
| `normal` | Búsqueda/paginación/flujo | 20 | 8 | 1.200 | 288 | 14.400 | 5 | 3,01 s | 36 MB |
| `large` `0.8` | Bloques/estrés inicial | 80 | 16 | 6.400 | 512 | 32.768 | 4 | 4,85 s | 46 MB |
| `edge` | Unicode, límites y estados | 5 | 2 | 9 | 32 | 288 | 5 | 1,58 s | 30 MB |

La fila `large` usa `TESTING_LARGE_MULTIPLIER=0.8`. Con multiplicador `1` genera configuración objetivo: 20 carreras, 10 materias/carrera, 5 grupos/materia, 500 docentes/carrera, 20 gestiones y 5 periodos: aproximadamente 100.000 designaciones legado. Inserción se hace en bloques de 1.000; no mantiene cientos de miles de modelos Eloquent en memoria.

## Usuarios sintéticos

- `vicerrectorado.testing@example.test`
- `director.tst01.01.testing@example.test` y directores equivalentes por carrera/índice.
- Perfil pequeño: un Vicerrectorado + cuatro directores.
- No se usan correos `uatf.edu.bo`, nombres de funcionarios ni datos copiados.

## Comandos

Para iniciar PostgreSQL sin borrar registros existentes, usar `pg_ctl` como
se documenta en `LEVANTAR_SERVIDOR_TESTING.md`. No usar `composer test:phase0`
como comando de inicio: Fase 0 ejecuta `migrate:fresh`, rollback y migracion
para validar el ciclo de vida, y deja la base sin datos sembrados.

Recrear base de testing antes de cada perfil:

```powershell
php artisan migrate:fresh --env=testing --force
php artisan db:seed --env=testing --class="Database\Seeders\Testing\TestingSmallSeeder" --force
```

Perfiles directos:

```powershell
php artisan db:seed --env=testing --class="Database\Seeders\Testing\TestingNormalSeeder" --force
php artisan db:seed --env=testing --class="Database\Seeders\Testing\TestingEdgeCasesSeeder" --force
$env:TESTING_LARGE_MULTIPLIER='0.8'
php artisan db:seed --env=testing --class="Database\Seeders\Testing\TestingLargeSeeder" --force
```

Selector explícito:

```powershell
$env:TESTING_DATASET_PROFILE='small'
php artisan db:seed --env=testing --class="Database\Seeders\Testing\TestingDatabaseSeeder" --force
```

Sin `TESTING_DATASET_PROFILE`, selector lanza error y no modifica datos.

## Validación automática

Cada perfil llama `TestingDatasetValidator`, que verifica:

- Roles permitidos y combinación rol/carrera.
- Emails bajo `example.test`, sin duplicados.
- Estados permitidos.
- Compatibilidad grupo/malla/materia.
- Una versión pendiente por propuesta.
- Un snapshot por grupo/versión y una decisión por snapshot.
- Claves foráneas y unicidades, delegadas también a PostgreSQL.

Salida incluye conteo de `users`, catálogos, legado, propuestas, snapshots, decisiones, eventos y notificaciones.

## Reproducibilidad

- Siglas, CI, correos, nombres sintéticos y códigos se derivan de índices estables.
- Gestiones, periodos y fechas usan valores definidos por perfil.
- Estados se distribuyen por índice, no por azar.
- `migrate:fresh` + mismo seeder reproduce cantidades y distribución general.
- Reejecución sobre base poblada no es operación objetivo; recrear base primero evita conflictos de unicidad.

## Limpieza y uso por fase

```powershell
php artisan migrate:fresh --env=testing --force
```

- Fase 3: `small` y `edge` para validar factories/datos.
- Fase 4: `small`/`normal` para integridad y rollback.
- Fase 5/6: `small`/`edge` para navegación y estados.
- Fase 7/8: `normal`/`large` escalado; no ejecutar carga en producción.

## Reglas pendientes

Los datasets no fijan mínimo/máximo de horas, alcance de Vicerrectorado, cardinalidad de propuestas ni precedencia definitiva entre legado y propuestas. Permanecen `NEEDS_BUSINESS_CONFIRMATION` según [BUSINESS_RULES.md](BUSINESS_RULES.md).

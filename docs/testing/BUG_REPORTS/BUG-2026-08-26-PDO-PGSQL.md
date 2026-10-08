# BUG-2026-08-26: Conexion PostgreSQL no disponible en produccion

## Estado

RESUELTO

## Sintoma

`GET /designaciones` devuelve HTTP 500. El endpoint `/health` informa que solo
la comprobacion de base de datos falla.

Los registros muestran repetidamente:

```text
production.WARNING: Lista Jachasun no disponible. {"exception":"PDOException"}
```

## Reproduccion segura

No se consultaron datos ni se ejecutaron escrituras. En el runtime PHP del
servidor:

```text
PHP 8.4.24
PDO::getAvailableDrivers() => []
```

No existe `pdo_pgsql.ini` en las configuraciones de CLI ni PHP-FPM.

## Impacto

La aplicacion no puede abrir la conexion PostgreSQL necesaria para consultar
las funciones de lectura de Jachasun.

## Causa probable

Falta el driver `pdo_pgsql` en el PHP 8.4 usado por PHP-FPM. Tambien existe una
desalineacion secundaria entre `DB_CONNECTION=pgsql`, la configuracion fuente y
la cache de configuracion de Laravel.

## Correccion prevista

1. Instalar y habilitar el modulo PostgreSQL para el PHP 8.4 de PHP-FPM.
2. Alinear `config/database.php` y el servicio con la conexion canonica `pgsql`.
3. Regenerar la cache de configuracion y reiniciar PHP-FPM de forma controlada.
4. Validar `/health`, `/designaciones` y el detalle sin ejecutar migraciones ni
   escrituras.

## Riesgos

- No existe respaldo reciente confirmado.
- La suite PHPUnit completa no puede ejecutarse hasta instalar sus dependencias.
- `app:health` no se ejecuto porque su comprobacion de cache realiza una
  escritura temporal.

## Resolucion y evidencia

Se habilito `pdo_pgsql` para PHP 8.4 y se reinicio PHP-FPM. Tambien se
sincronizo la cache de Laravel con `DB_CONNECTION=pgsql` y el usuario actual
`usr_designaciones`.

Resultado de las pruebas de solo lectura:

- `PDO::getAvailableDrivers()` incluye `pgsql`.
- El puerto PostgreSQL es accesible.
- `SELECT 1` conecta correctamente.
- `f_asignaciones('INF', '0', '0')` devuelve 47 registros totales.
- `f_asignaciones_detalles(id)` devuelve 69 filas para el primer registro.

No se ejecutaron migraciones ni operaciones de escritura. La suite PHPUnit
completa queda pendiente porque la dependencia no esta instalada.

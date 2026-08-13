# Operacion segura

## Ambientes

Jachasun es la base principal de ejecucion. Testing usa PostgreSQL aislado y
nunca comparte credenciales ni datos con Jachasun.

`migrate:fresh`, seeders destructivos y datos sinteticos solo se ejecutan en
testing autorizado. Nunca usar produccion ni Jachasun real para la suite.

## Configuracion

La conexion se configura en `.env` mediante `DB_*`:

```env
DB_CONNECTION=jachasun
DB_HOST=...
DB_PORT=5432
DB_DATABASE=jachasun
DB_USERNAME=...
DB_PASSWORD=...
```

Las credenciales se mantienen fuera de Git y nunca se escriben en logs.

## Testing

```bash
cp .env.testing.example .env.testing
php artisan migrate --env=testing --force
composer test -- --env=testing
vendor/bin/pint --test
git diff --check
```

La suite usa mocks para la funcion Jachasun y no necesita acceso a la base
universitaria real.

## Salud y despliegue

Verificar `GET /health`, `php artisan app:health`, permisos de `storage` y
`bootstrap/cache`, HTTPS, backups, restauracion, logs y workers antes de
habilitar un ambiente. No registrar secretos ni filas completas de Jachasun.

## Consulta Jachasun

`/designaciones` consulta la carrera del director con
`f_asignaciones(sigla, '0', '0')`. No existe una bandera de activacion ni una
segunda pantalla institucional. La consulta usa parametros enlazados y una
transaccion `READ ONLY`; los fallos responden con un mensaje seguro.

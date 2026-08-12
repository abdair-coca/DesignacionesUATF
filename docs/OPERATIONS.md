# Operacion segura

## Ambientes

Mantener separadas las bases y credenciales de local, testing, staging y
produccion. La base institucional externa no es `DB_CONNECTION` y no recibe
migraciones de Laravel.

`migrate:fresh`, seeders destructivos y datos sinteticos solo se ejecutan en
local/testing autorizados. Nunca usar produccion ni Jachasun para la suite.

## Instalacion local

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Configurar la base propia antes de migrar. Las credenciales se mantienen en
`.env`, fuera de Git.

## Testing

```bash
cp .env.testing.example .env.testing
php artisan migrate --env=testing --force
composer test -- --env=testing
vendor/bin/pint --test
git diff --check
```

La suite usa mocks para Jachasun y no necesita acceso a la base universitaria.

## Salud y despliegue

Verificar `GET /health`, `php artisan app:health`, permisos de `storage` y
`bootstrap/cache`, HTTPS, backups, restauracion, logs y workers antes de
habilitar un ambiente. No registrar secretos ni respuestas institucionales
completas en logs.

## Integracion institucional

`INSTITUTIONAL_ENABLED=false` es el valor predeterminado. Activarla requiere
un ambiente autorizado, credenciales suministradas por administracion y una
prueba del contrato documentado en
[`INTEGRATION_JACHASUN.md`](INTEGRATION_JACHASUN.md). La aplicacion solo usa
la funcion permitida en una transaccion `READ ONLY`.

Con la integracion habilitada, `/designaciones` consulta siempre Jachasun para
la carrera del director con `0`/`0`. No mezcla propuestas locales ni utiliza
datos locales como respaldo. Si Jachasun falla o esta deshabilitado, responde
HTTP 503 con un mensaje seguro.

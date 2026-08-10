# Guia de despliegue de Designaciones UATF

Esta guia prepara una instalacion reproducible. No reemplaza la aprobacion del
administrador de servidores ni autoriza conectarse a la base institucional.

## 1. Arquitectura de despliegue

```text
Usuario
  |
HTTPS
  |
Nginx o IIS
  |
public/index.php -> PHP-FPM/PHP
  |
PostgreSQL de la aplicacion

PHP -> funciones institucionales autorizadas (Fase 4, conexion secundaria)
```

La base de la aplicacion debe estar separada de la base institucional. Laravel
usa `DB_CONNECTION` para su base propia. La futura conexion institucional nunca
debe reemplazar esa variable.

## 2. Informacion que debe entregar la universidad

Antes de instalar, solicita al administrador:

- sistema operativo y arquitectura;
- PHP y extensiones habilitadas;
- Composer;
- PostgreSQL y nombre de la base propia de la aplicacion;
- Nginx/PHP-FPM o IIS;
- dominio y certificado HTTPS;
- usuario de servicio;
- permisos de filesystem;
- firewall y salida hacia el servidor de funciones;
- politica de respaldos;
- ventana de mantenimiento;
- servicio para workers de cola;
- ubicacion y retencion de logs.

No ejecutes comandos de instalacion hasta identificar el ambiente como
`local`, `staging` o `production`.

## 3. Preflight del servidor

Ejecuta como usuario de despliegue:

```bash
php -v
php -m
composer check-platform-reqs --no-dev
psql --version
```

Extensiones importantes para este proyecto: `pdo_pgsql`, `openssl`, `mbstring`,
`xml`, `ctype`, `fileinfo`, `json`, `tokenizer` y `zip`.

La version de PHP debe satisfacer `composer.json`, actualmente `^8.3`.

## 4. Primera instalacion

Crear la base propia de la aplicacion y un usuario PostgreSQL sin permisos
administrativos. No reutilizar el usuario propietario del servidor.

```bash
composer install --no-dev --prefer-dist --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

Completar `.env` fuera de Git:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://designaciones.example.edu

DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=designaciones_uatf_app
DB_USERNAME=designaciones_app
DB_PASSWORD=valor-secreto-fuera-de-git

SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=database
FILESYSTEM_DISK=local
INSTITUTIONAL_ENABLED=false
```

La clave debe generarse una sola vez y conservarse. No ejecutes `key:generate`
en una actualizacion porque invalidaria sesiones cifradas.

Aplicar el esquema solamente a la base propia:

```bash
php artisan migrate --force
php artisan storage:link
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan app:health
```

No usar `migrate:fresh`, seeders de testing ni `DatabaseSeeder` en producción.

## 5. Nginx y PHP-FPM

El document root debe ser únicamente la carpeta `public/`, nunca la raiz del
repositorio. El servidor debe enviar los archivos PHP a PHP-FPM y negar acceso
a `.env`, `storage/`, `vendor/` y archivos de configuración.

Comprobaciones mínimas:

- `public/index.php` es el front controller;
- HTTPS redirige desde HTTP;
- el certificado es válido;
- PHP-FPM usa la version compatible;
- `storage` y `bootstrap/cache` son escribibles por el usuario de servicio;
- el resto del repositorio no es descargable.

## 6. IIS

Si la universidad entrega Windows/IIS, se debe configurar:

- PHP mediante FastCGI;
- URL Rewrite hacia `public/index.php`;
- document root en `public`;
- permisos de escritura solo para `storage` y `bootstrap/cache`;
- bloqueo de `.env`, `vendor` y archivos ocultos;
- HTTPS y certificado institucional.

La configuracion exacta depende de la version de IIS y debe probarse primero en
staging.

## 7. Actualizacion de una version

Publicar cada version en un directorio separado o mediante el mecanismo de
releases del servidor. Antes de cambiar la version:

```bash
php artisan down --retry=60
pg_dump --format=custom --file=/ruta/backup/pre-release.dump designaciones_uatf_app
composer install --no-dev --prefer-dist --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan app:health
php artisan up
```

Si cualquier paso falla, deja el sitio en mantenimiento y ejecuta el rollback
documentado. No ocultes un error continuando con la siguiente orden.

## 8. Health checks

CLI, util para el administrador:

```bash
php artisan app:health
php artisan app:health --json
```

HTTP, util para el proxy o monitor:

```bash
curl --fail https://designaciones.example.edu/health
```

Estados:

- `200` y `status: ok`: aplicacion, base propia, cache y filesystem disponibles.
- `503` y `status: degraded`: algun requisito critico falla.
- `institutional: not_configured`: correcto antes de la Fase 4.
- `institutional: blocked`: alguien activo la integracion antes de tener
  adaptador; no se debe desplegar asi.

El endpoint no requiere login y no muestra contrasenas, DSN, excepciones ni
datos de negocio.

## 9. Permisos y logs

El usuario que ejecuta PHP debe poder escribir solamente en:

```text
storage/
bootstrap/cache/
```

Los logs deben rotarse y tener retencion definida. No se deben registrar:

- contrasenas;
- claves de API;
- `DB_PASSWORD`;
- resultados completos de funciones institucionales si contienen datos
  sensibles;
- cookies o sesiones.

## 10. Cola y cache

La configuracion de producción usa base PostgreSQL para cache, sesiones y cola.
El servidor debe ejecutar un worker persistente:

```bash
php artisan queue:work --sleep=3 --tries=3 --max-time=3600
```

El worker debe estar administrado por Supervisor, systemd o el mecanismo
equivalente de Windows. Si el worker se detiene, las notificaciones y trabajos
pendientes pueden acumularse.

## 11. Respaldos y restauracion

Respaldar la base propia, no la base institucional:

```bash
pg_dump --format=custom --file=/ruta/backup/designaciones-YYYYMMDD.dump designaciones_uatf_app
```

Reglas:

- hacer respaldos antes de migraciones;
- guardar copias en una ubicacion separada;
- cifrar y restringir el acceso;
- conservar varias generaciones;
- probar restauracion en otra base;
- registrar fecha, tamaño y resultado de cada respaldo.

Restaurar primero en una base nueva de validacion:

```bash
createdb designaciones_uatf_restore_check
pg_restore --dbname=designaciones_uatf_restore_check /ruta/backup/designaciones-YYYYMMDD.dump
```

No elimines la base actual para probar un restore. El cambio de base se hace
solo despues de verificar migraciones, conteos, login y health check.

## 12. Rollback

Hay dos rollbacks distintos:

1. **Codigo**: volver al release anterior y limpiar/cachear configuracion.
2. **Datos**: restaurar un backup validado en una base separada y cambiar la
   conexion durante una ventana autorizada.

No uses `migrate:rollback` como estrategia general de recuperación en
producción. Una migracion puede haber cambiado datos y no siempre es reversible
de forma segura.

## 13. Checklist de liberacion

- [ ] El ambiente y la base fueron identificados.
- [ ] No hay credenciales en Git.
- [ ] `APP_DEBUG=false` en staging/produccion.
- [ ] `APP_KEY` existe y no se regenerara durante actualizaciones.
- [ ] La base de Laravel es propia y PostgreSQL responde.
- [ ] `INSTITUTIONAL_ENABLED=false` hasta la Fase 4.
- [ ] Migraciones ejecutadas solo en la base propia.
- [ ] `/health` devuelve HTTP 200.
- [ ] Login de prueba funciona en staging.
- [ ] El Director no ve otra carrera.
- [ ] Vicerrectorado puede revisar una propuesta de prueba.
- [ ] Se creo un backup y se verifico su archivo.
- [ ] Existe rollback escrito y probado.
- [ ] Logs y worker estan operativos.
- [ ] HTTPS es valido.

## 14. Que sigue

La Fase 4 definira el contrato de funciones institucionales y construira un
adaptador simulado. No se debe activar `INSTITUTIONAL_ENABLED` antes de esa
fase.

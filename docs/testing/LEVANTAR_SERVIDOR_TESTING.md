# Levantar el entorno de testing

Guía para ejecutar el sistema con la base de datos local aislada de testing.

## Seguridad

- Usar únicamente `--env=testing`.
- La base utilizada es `designaciones_uatf_testing`.
- No usar credenciales reales ni conectarse a producción.
- `migrate:fresh` elimina y recrea todos los datos de la base de testing.

## 1. Abrir PowerShell en el proyecto

```powershell
Set-Location "C:\Users\abdai\Desktop\Intership\DesginacionesUATF"
```

Verificar que exista `.env.testing`:

```powershell
Test-Path .env.testing
```

Si devuelve `False`, crear la configuración local:

```powershell
Copy-Item .env.testing.example .env.testing
```

La configuración debe apuntar a:

```text
APP_ENV=testing
DB_HOST=127.0.0.1
DB_PORT=55432
DB_DATABASE=designaciones_uatf_testing
SESSION_DRIVER=file
```

## 2. Levantar PostgreSQL de testing

Inicio normal: no ejecuta migraciones ni borra datos.

Opción recomendada:

```powershell
$pgctl = (Get-Command pg_ctl.exe -ErrorAction Stop).Source
$testingData = (Resolve-Path "storage/app/testing/postgres").Path
$testingLog = Join-Path $testingData "server.log"

& $pgctl -D $testingData status
if ($LASTEXITCODE -ne 0) {
    & $pgctl -D $testingData -l $testingLog -o '"-p 55432"' -w start
}
```

Este comando inicia, si hace falta, el clúster PostgreSQL aislado en:

```text
storage/app/testing/postgres
```

Importante: no usar `composer test:phase0` para iniciar normalmente. Ese comando ejecuta una validacion destructiva de Fase 0 que incluye `migrate:fresh`, rollback y migracion; deja la base sin los usuarios del seeder.

Verificar que el puerto esté disponible:

```powershell
Test-NetConnection 127.0.0.1 -Port 55432
```

Debe mostrar:

```text
TcpTestSucceeded : True
```

### Validacion destructiva opcional de Fase 0

Usar solo cuando se quiera validar Fase 0 desde una base limpia:

```powershell
composer test:phase0 # validacion destructiva de Fase 0
```

Despues de ejecutarlo, repetir obligatoriamente el paso 3 para volver a crear los registros de testing.

## 3. Crear el esquema y los registros de prueba

Este paso deja una base pequeña para probar manualmente:

```powershell
php artisan migrate:fresh --env=testing --force
php artisan db:seed --env=testing --class="Database\Seeders\Testing\TestingSmallSeeder" --force
```

En PowerShell se debe escribir una sola barra invertida (`\`) en el nombre de la clase. No usar `\\` ni `\` como continuación de línea.

El perfil pequeño crea aproximadamente:

```text
Usuarios:       5
Carreras:       4
Designaciones:  432
Propuestas:     5
Notificaciones: 4
```

## 4. Limpiar configuración y levantar Laravel

En una segunda ventana de PowerShell:

```powershell
Set-Location "C:\Users\abdai\Desktop\Intership\DesginacionesUATF"
php artisan config:clear
php artisan serve --env=testing --host=127.0.0.1 --port=8000
```

Abrir en el navegador:

```text
http://127.0.0.1:8000/login
```

## 5. Credenciales sintéticas

### Vicerrectorado

```text
Correo:      vicerrectorado.testing@example.test
Contraseña:  testing-password
```

### Director

```text
Correo:      director.tst01.01.testing@example.test
Contraseña:  testing-password
```

Estas cuentas solo existen en la base de testing. No representan cuentas institucionales.

## 6. Probar el sistema

Después de iniciar sesión:

- Vicerrectorado: revisar `/revisiones/pendientes`.
- Director: revisar `/designaciones`.
- Abrir el botón de notificaciones.
- Usar `Ver todo` para abrir `/notificaciones`.
- Probar credenciales incorrectas y verificar el mensaje de error.

## 7. Ejecutar pruebas automatizadas

Ejecutar la suite desde otra ventana:

```powershell
composer test
vendor/bin/pint --test
git diff --check
```

La suite puede usar `RefreshDatabase` y dejar vacía la base de testing. Después de ejecutar `composer test`, repetir el paso 3 antes de probar manualmente el navegador.

## 8. Solución rápida de errores

### Error `connection refused` en el puerto `55432`

PostgreSQL testing está apagado. Ejecutar:

```powershell
$pgctl = (Get-Command pg_ctl.exe -ErrorAction Stop).Source
$testingData = (Resolve-Path "storage/app/testing/postgres").Path
& $pgctl -D $testingData -l "storage/app/testing/postgres.log" -o '"-p 55432"' -w start
```

### El login dice `Credenciales incorrectas`

Verificar si existen usuarios:

```powershell
php artisan tinker --env=testing --execute="echo App\Models\User::count();"
```

Si devuelve `0`, repetir el paso 3.

### El login vuelve a mostrar el formulario

Verificar que la sesión no use `array`:

```powershell
php artisan tinker --env=testing --execute="echo config('session.driver');"
```

Debe devolver:

```text
file
```

Luego ejecutar:

```powershell
php artisan config:clear
```

### Error `Target class ... Seeder does not exist`

En PowerShell usar el nombre con una sola barra invertida:

```powershell
php artisan db:seed --env=testing --class="Database\Seeders\Testing\TestingSmallSeeder" --force
```

## 9. Apagar el entorno

En la ventana donde corre Laravel, presionar:

```text
Ctrl + C
```

Detener PostgreSQL testing:

```powershell
$pgctl = (Get-Command pg_ctl.exe -ErrorAction Stop).Source
$testingData = (Resolve-Path "storage/app/testing/postgres").Path
& $pgctl -D $testingData stop
```

Este procedimiento no detiene ni modifica la instancia PostgreSQL normal del puerto `5432`.

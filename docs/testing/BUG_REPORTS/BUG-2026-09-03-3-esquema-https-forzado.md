# BUG-2026-09-03-3: Editar cabecera no guarda (esquema `https` forzado en URLs)

## Estado

RESUELTO (2026-09-03). Verificado en navegador real (Playwright/Chromium) y con
prueba de regresión `tests/Feature/UrlSchemeTest.php`.

## Severidad

Alta: ningún formulario de escritura (editar cabecera, crear, importar) guardaba
los datos desde el navegador.

## Ambiente

- Producción `http://asignaciones.uatf.edu.bo` (servidor sirve solo HTTP).
- Navegador: el `<form>` apunta a `https://...` pero el sitio se sirve por HTTP.

## Sintoma

Al editar la observación de una designación (`POST /designaciones/{id}`) y pulsar
Guardar/Confirmar, la vista recargaba sin cambios y sin ningún mensaje: la
observación no se guardaba.

## Reproduccion (navegador real)

1. Iniciar sesión como director de INF (`director.inf@uatf.edu.bo`).
2. Abrir `/designaciones/2137`.
3. Clic en "Editar" (cabecera), cambiar la observación, "Guardar cambios", "Confirmar".
4. Resultado real:

```
FORM action: https://asignaciones.uatf.edu.bo/designaciones/2137   (página en http)
REQ POST https://asignaciones.uatf.edu.bo/designaciones/2137
RES 301 https://.../designaciones/2137 -> location=http://asignaciones.uatf.edu.bo/designaciones/2137
```

El navegador recibe un 301 y convierte el POST en GET (especificación HTTP); el
controlador `update` nunca se ejecuta. La observación queda igual y no hay flash
success ni error.

## Causa raiz

`app/Providers/AppServiceProvider.php` llamaba `URL::forceScheme('https')`
incondicionalmente: todas las URLs generadas (form actions, redirects) usaban
`https://`. Pero el servidor solo sirve HTTP: toda petición a `https://...`
devuelve `301 -> http://...` (certificado autofirmado, vhost que redirige).
Verificado con `curl` para cualquier ruta, incluidas las autenticadas.

## Correccion aplicada

`AppServiceProvider@boot`: `forceScheme('https')` solo se aplica cuando la
aplicación declara servir por HTTPS (`APP_URL` empieza por `https://`). Con
`APP_URL=http://asignaciones.uatf.edu.bo` las URLs usan el esquema real del
request (HTTP), el POST llega al controlador y el guardado funciona.

## Prueba de regresion

- `tests/Feature/UrlSchemeTest.php` (`test_las_urls_generadas_usan_el_esquema_http_del_request`):
  con un request HTTP, `route('designaciones.update', ...)` debe generar `http://`
  y no `https://`. FALLABA antes del fix (`https://...`); PASA después.
- Verificación en navegador real sobre `2138` (con fecha): la observación se
  guardó (`flash success`, valor persistido) y se restauró el valor original
  (sin dejar cambios). Sobre `2137` (fecha NULL) el POST ahora llega al
  controlador (302 + flash), confirmando que el esquema quedó corregido.
- `vendor/bin/pint --test` OK en archivos modificados; `php -l` OK.

## Riesgos / pendientes

- La suite completa sigue bloqueada por la base de testing
  (`127.0.0.1:55432` / `designaciones_uatf_testing` no disponible), no por
  `phpunit` (ya instalado via `composer install`).
- Queda pendiente el bug de fecha nula (designaciones creadas por
  "Importar de una gestión anterior", p. ej. 2137 y 2129, tienen `fecha = NULL`):
  al editar su observación, `JachasunDesignacionesService::validarFecha('')`
  lanza y el controlador muestra "No fue posible actualizar la designacion.".
  Es la Solución 2 propuesta, no incluida en este cambio.
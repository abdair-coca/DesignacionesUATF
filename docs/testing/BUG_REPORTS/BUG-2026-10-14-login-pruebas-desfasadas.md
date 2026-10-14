# BUG-2026-10-14-login-pruebas-desfasadas: pruebas de login con expectativas viejas

## Severidad

P3 — las pruebas de login no validaban la redirección vigente ni el mensaje de
bloqueo. El login real funcionaba.

## Módulo

Pruebas de autenticación (`tests/Feature/Auth/GuestAccessTest.php`).

## Ambiente

PostgreSQL de testing aislado, base limpia y migrada. Ejecución con
`php artisan test --env=testing --no-coverage tests/Feature/Auth/GuestAccessTest.php`.

## Precondiciones

Código de `main` (`0a7c527`) y de las fases P1 a P5.

## Pasos para reproducir

1. Ejecutar `GuestAccessTest`.
2. Observar 3 fallas entre las 12 ejecuciones (9 métodos y 3 casos del data provider).

## Resultado actual antes de corregir

- `test_login_valido_redirige` y `test_login_valido_limpia_el_contador_de_intentos`
  esperaban redirección a `/notificaciones`. La aplicación redirige a
  `/designaciones` para un director.
- `test_login_invalido_se_bloquea_despues_de_cinco_intentos` fallaba en
  `assertStringContainsString`: el texto esperado no coincidía con el mensaje.

## Resultado esperado

- Las pruebas esperan la redirección vigente por rol.
- La aserción de bloqueo coincide con el mensaje real.

## Causa raíz

Verificada, en las pruebas:

1. La redirección del login depende del rol en
   `AuthenticatedSessionController::rutaInicial`. `PageAccessTest` ya verifica que
   un director entra a `/designaciones`. La expectativa de `/notificaciones` es
   anterior a esa lógica.
2. La línea 64 contenía el carácter de reemplazo Unicode (U+FFFD) en lugar de `ó`
   en "sesión". Con esa codificación la aserción nunca podía coincidir.

## Descartado

- Falla de la lógica de límite de intentos: el mensaje real aparece con el texto
  esperado, salvo por la codificación.

## Corrección aplicada (14/10/2026)

- Las dos expectativas de redirección ahora esperan `/designaciones`, porque el
  usuario de fábrica es director.
- Se reemplazó U+FFFD por `ó` en la aserción de bloqueo.

## Prueba de regresión

- `GuestAccessTest` pasa completo: 12/12 ejecuciones.

## Estado

Corregido el 14/10/2026.

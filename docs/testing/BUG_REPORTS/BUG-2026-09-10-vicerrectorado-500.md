# BUG-2026-09-10: Vicerrectorado recibia 500 en designaciones

Estado: **RESUELTO**

## Reproduccion

Un usuario Vicerrectorado autenticado accedia directamente a
`/designaciones`. Como no tiene carrera asociada, el controlador lanzaba una
`LogicException` al intentar construir el alcance de carrera y la respuesta
era HTTP 500.

## Correccion

El limite de autorizacion ahora exige `director_carrera` y responde HTTP 403
antes de intentar leer la carrera. El login del Vicerrectorado conserva su
redireccion a `/notificaciones`.

## Verificacion

- Prueba feature de autorizacion: cubierta.
- Navegador UATF: login Vicerrectorado a `/notificaciones`; acceso directo a
  `/designaciones` devuelve 403.
- No se realizaron escrituras en la BD real.

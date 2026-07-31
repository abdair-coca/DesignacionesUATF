# Boceto interactivo de Designaciones

## Objetivo

El boceto presenta el flujo completo de Designaciones para validacion visual y de experiencia de usuario. Es una pieza independiente de la interfaz operativa: no usa autenticacion, rutas de negocio, modelos, base de datos ni llamadas al backend.

## Acceso local

Con `php artisan serve` en ejecucion, abrir:

```text
http://127.0.0.1:8000/boceto-designaciones/
```

La unica pagina del boceto concentra todas las vistas; no hay pantallas de etapa separadas.

## Perfiles simulados

El selector del avatar permite alternar entre:

- Mgtr. Maria Quispe, Direccion de Ingenieria Informatica.
- Ing. Carlos Flores, Direccion de Ingenieria Civil.
- Dra. Ana Rojas, Direccion de Medicina.
- Dr. Ricardo Villca, Vicerrectorado Academico.

## Flujo de Director

1. Consultar `Mis propuestas de designacion` con estados de borrador, enviada, observada y aprobada/bloqueada.
2. Crear una propuesta vacia o copiar una propuesta anterior.
3. Editar designaciones, guardar el borrador, importar asignaciones y revisar el historial.
4. Enviar la propuesta a revision o retirar el envio mientras esta pendiente.
5. Si Vicerrectorado observa una designacion, corregirla y reenviar una nueva version.

## Flujo de Vicerrectorado

1. Abrir la bandeja de revisiones con patron visual de correo de Color Admin.
2. Consultar una propuesta enviada en modo solo lectura.
3. Registrar observaciones globales o por designacion. Vicerrectorado no edita asignaciones.
4. Mientras exista una designacion observada, la aprobacion total permanece deshabilitada.
5. Aprobar la propuesta corregida. La aprobacion bloquea la propuesta y sus designaciones.

## Alcance visual

El boceto usa HTML, CSS y JavaScript puros y la referencia visual de Color Admin V2: sidebar grafito, cabeceras institucionales, paneles, tablas, badges, modales, botones y bandeja de revisiones. Esta optimizado para revision en computadora; no busca resolver la version responsive en esta etapa.

## Limites

- Los datos, perfiles, notificaciones y estados son simulados y se reinician al recargar la pagina.
- No sustituye las pruebas funcionales del sistema Laravel.
- No modifica el flujo operativo existente.

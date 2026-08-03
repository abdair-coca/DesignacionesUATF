# Boceto interactivo de Designaciones

## Objetivo

El boceto presenta el flujo completo de Designaciones para validacion visual,
funcional y de experiencia de usuario. Es una pieza independiente de la
interfaz operativa: no usa autenticacion, rutas de negocio, modelos, base de
datos ni llamadas al backend.

La especificacion funcional detallada esta en
[Logica de negocio y flujo completo del boceto](LOGICA_NEGOCIO_Y_FLUJO_BOCETO.md).
Ese documento es la referencia para trasladar el comportamiento a un sistema
real.

## Acceso local

Con `php artisan serve` en ejecucion, abrir:

```text
http://127.0.0.1:8000/boceto-designaciones/
```

Tambien puede servirse directamente la carpeta `public/boceto-designaciones`
con un servidor estatico. La unica pagina del boceto concentra todas las
vistas; no hay pantallas de etapa separadas.

## Perfiles simulados

El selector del avatar permite alternar entre:

- Mgtr. Maria Quispe, Direccion de Ingenieria Informatica;
- Ing. Carlos Flores, Direccion de Ingenieria Civil;
- Dra. Ana Rojas, Direccion de Medicina;
- Dr. Ricardo Villca, Vicerrectorado Academico.

El aislamiento por carrera es visual y simulado. El sistema real debe
reforzarlo en politicas, consultas y endpoints.

## Flujo de Director

1. Consultar `Mis propuestas de designacion` con estados de borrador,
   pendiente, observada y aprobada/bloqueada.
2. Crear una propuesta vacia o un borrador inicializado desde una propuesta
   historica de la misma carrera.
3. Abrir el editor por docente y asignar materias/grupos elegibles.
4. Registrar por cada materia las horas pagadas, no pagadas y una
   justificacion opcional.
5. Validar que la distribucion sea entera, no negativa, que las horas pagadas
   no superen las oficiales y que el total cubra la materia.
6. Importar asignaciones historicas, incluyendo la distribucion de horas.
7. Enviar la propuesta a revision o retirar el envio mientras esta pendiente.
8. Si Vicerrectorado observa una designacion, corregirla y reenviar una nueva
   version.

Las materias ya asignadas a otros docentes aparecen bloqueadas como opciones
de seleccion. Las filas aprobadas previamente tambien son inmutables.

## Flujo de Vicerrectorado

1. Abrir la bandeja de revisiones con patron visual de correo de Color Admin.
2. Consultar una propuesta enviada en modo de revision.
3. Ver horas oficiales, pagadas, no pagadas y adicionales no pagadas.
4. Registrar observaciones globales o por designacion.
5. Aprobar u observar cada fila sin editar sus horas ni su docente.
6. Aprobar la propuesta completa cuando las decisiones de las filas sean de
   aprobacion.
7. Devolverla al Director cuando exista una observacion.

La aprobacion bloquea la propuesta y sus designaciones. La distribucion de
horas queda incluida en el snapshot de la version.

## Distribucion de horas

Para una materia de 6 horas, el boceto acepta:

```text
6 pagadas + 0 no pagadas
4 pagadas + 2 no pagadas
0 pagadas + 6 no pagadas
6 pagadas + 2 no pagadas
```

Las horas adicionales no pagadas no tienen limite automatico y cuentan para la
carga docente. La formula es:

```text
horas_totales = horas_pagadas + horas_no_pagadas
horas_adicionales_no_pagadas = max(0, horas_totales - horas_oficiales)
```

## Alcance visual

El boceto usa HTML, CSS y JavaScript puros y la referencia visual de Color
Admin V2: sidebar grafito, cabeceras institucionales, paneles, tablas,
badges, modales, botones y bandeja de revisiones. Esta optimizado para
revision en computadora; no busca resolver la version responsive en esta
etapa.

## Limites

- Los datos, perfiles, notificaciones, snapshots y estados son simulados y se
  reinician al recargar la pagina.
- Los snapshots se registran en memoria, pero el boceto no incluye un visor
  para navegar el historial completo de versiones.
- No sustituye las pruebas funcionales del sistema Laravel.
- La seguridad de roles y la inmutabilidad son reglas visuales; el sistema real
  debe imponerlas en el servidor y la base de datos.
- No existe una bolsa monetaria ni calculo salarial.
- Las horas de otras carreras se representan como un total, sin desglose de
  remuneracion.

# Estado del Flujo de Designaciones

## Implementado

- El Director trabaja en `/designaciones` y solo ve la informacion de su carrera.
- Cada propuesta abre una pantalla detallada de asignacion por docente; permite asignar por grupo, importar historicos, enviar o retirar la version pendiente y consultar el historial de revisiones.
- El borrador conserva filas aprobadas previamente como solo lectura.
- Cada envio crea una version inmutable y notifica a Vicerrectorado.
- Vicerrectorado usa `/revisiones/pendientes` para aprobar una revision completa o decidir por fila.
- Las observaciones devuelven el mismo borrador al Director; el reenvio crea una nueva version.
- La aprobacion final bloquea la propuesta y sus filas. Las notificaciones informan al Director y sus enlaces usan rutas vigentes.
- La distribucion por materia/grupo conserva `horas_pagadas`, `horas_no_pagadas` y una justificacion opcional.
- Las horas no pagadas y las horas adicionales no pagadas cuentan para la carga docente total.
- Las horas pagadas no pueden superar las horas oficiales y la suma de ambas distribuciones debe cubrir la materia.

## Boceto de validacion visual

Existe un boceto independiente en `/boceto-designaciones/` para revisar el flujo sin inicio de sesion ni backend. Reune en una sola pagina las vistas del Director y Vicerrectorado, con datos simulados y patron visual Color Admin V2.

- El selector del avatar alterna entre tres Directores de Carrera y Vicerrectorado.
- Director puede crear/copia una propuesta, editar, importar, guardar, enviar, retirar envio y consultar el historial.
- Director puede distribuir cada materia entre horas pagadas y no pagadas, incluyendo horas adicionales no pagadas sin limite automatico.
- Vicerrectorado solo puede revisar, observar o aprobar; no puede editar designaciones ni la distribucion de horas.
- Una observacion por designacion devuelve la propuesta al Director para que corrija y reenvie una nueva version.
- La aprobacion final del boceto bloquea visualmente la propuesta y sus designaciones.

Los detalles de uso y alcance estan en [Boceto interactivo](BOCETO_INTERACTIVO.md) y la especificacion completa esta en [Logica de negocio y flujo del boceto](LOGICA_NEGOCIO_Y_FLUJO_BOCETO.md).

La distribucion de remuneracion descrita arriba esta implementada actualmente
en el boceto. La migracion de base de datos, snapshots, modelos, servicios y
validaciones de servidor para el sistema real debe seguir la especificacion
canonica antes de habilitarla en produccion.

## Pendiente para completar el flujo integral

La funcionalidad esta construida, pero todavia falta la validacion integral de aceptacion con usuarios y datos representativos. Esta es la condicion pendiente para declarar el flujo completo de Designaciones como operativo:

1. Ejecutar una prueba de punta a punta en PostgreSQL local con un Director y Vicerrectorado: crear borrador, asignar, importar, enviar, observar, corregir, reenviar y aprobar.
2. Repetir la prueba con dos carreras para comprobar el aislamiento de datos y con una fila aprobada previamente para confirmar su bloqueo.
3. Verificar desde navegador la lista de Designaciones, la bandeja de Revisiones, los filtros, la busqueda, los enlaces de notificaciones y los mensajes de validacion.
4. Registrar las observaciones de los usuarios responsables y resolverlas antes de habilitar el uso operativo con datos institucionales.
5. Definir por separado las reglas academicas que aun no estan aprobadas, como limites de carga horaria o formatos de reporte. El sistema no debe inventarlas ni bloquear por ellas.

## Criterio de cierre

El flujo se considerara completo cuando las pruebas anteriores pasen sin correcciones pendientes y los responsables confirmen que las pantallas y estados reflejan su proceso real.

# Estado del Flujo de Designaciones

## Implementado

- El Director trabaja en `/designaciones` y solo ve la informacion de su carrera.
- Cada propuesta abre una pantalla detallada de asignacion por docente; permite asignar por grupo, importar historicos, enviar o retirar la version pendiente y consultar el estado y las observaciones de la propuesta.
- El borrador conserva filas aprobadas previamente como solo lectura.
- Cada envio crea una version inmutable y notifica a Vicerrectorado.
- Vicerrectorado usa `/revisiones/pendientes` para aprobar una revision completa o decidir por fila.
- Las observaciones devuelven el mismo borrador al Director; el reenvio crea una nueva version.
- La aprobacion final bloquea la propuesta y sus filas. Las notificaciones informan al Director y sus enlaces usan rutas vigentes.
- La distribucion por materia/grupo conserva `horas_pagadas`, `horas_no_pagadas` y una justificacion opcional.
- La revision de Vicerrectorado muestra `Justificacion de remuneracion` por materia/grupo en versiones pendientes, observadas y aprobadas; es solo lectura, conserva el texto completo y usa `—` cuando esta vacia.
- La tabla de revision se ajusta al ancho disponible, evita el scroll vertical de la pantalla y pagina las filas en bloques de cuatro sin perder las decisiones del formulario.
- La justificacion del Director se mantiene separada de las observaciones por fila y general de Vicerrectorado; cada snapshot conserva el valor de su propia version.
- Las horas no pagadas y las horas adicionales no pagadas cuentan para la carga docente total.
- Las horas pagadas no pueden superar las horas oficiales y la suma de ambas distribuciones debe cubrir la materia.
- La bandeja de Vicerrectorado muestra la descripcion de cada propuesta para distinguir versiones con el mismo nombre.
- La revision real replica la cabecera oscura del boceto, el checkbox `Aprobar todas las filas` y el unico boton `Confirmar Revision`.
- El checkbox marcado aplica `Aprobar` a todas las filas; desmarcado aplica `Observar` y habilita sus motivos.
- Los selects siguen siendo editables individualmente; cambiar una fila manualmente desmarca el checkbox para evitar estados mixtos.
- El motivo por fila solo esta disponible cuando la decision es `Observar`; el servidor rechaza una fila `Aprobar` con motivo.
- Una fila observada asignada a otro docente no aparece como elegible para modificarla desde el docente equivocado.
- Al corregir una fila observada desaparecen su resaltado rojo y observacion del editor; la observacion original permanece en el historial.
- La descripcion de la propuesta tambien aparece en la cabecera de la pantalla de revision.
- La aceptación punta-a-punta con dos carreras está automatizada en `tests/Feature/DesignacionesAceptacionTest.php`: copia histórica, observación, corrección, reenvío, aprobación, notificaciones, snapshots, bloqueo y aislamiento.

## Boceto de validacion visual

Existe un boceto independiente en `/boceto-designaciones/` para revisar el flujo sin inicio de sesion ni backend. Reune en una sola pagina las vistas del Director y Vicerrectorado, con datos simulados y patron visual Color Admin V2.

- El selector del avatar alterna entre tres Directores de Carrera y Vicerrectorado.
- Director puede crear/copiar una propuesta, editar, importar, guardar, enviar, retirar envio y consultar estado u observaciones. Los snapshots de versiones se conservan en memoria, pero el boceto no tiene visor de historial.
- Director puede distribuir cada materia entre horas pagadas y no pagadas, incluyendo horas adicionales no pagadas sin limite automatico.
- Vicerrectorado solo puede revisar, observar o aprobar; no puede editar designaciones ni la distribucion de horas.
- Una observacion por designacion devuelve la propuesta al Director para que corrija y reenvie una nueva version.
- La aprobacion final del boceto bloquea visualmente la propuesta y sus designaciones.

Los detalles de uso y alcance estan en [Boceto interactivo](BOCETO_INTERACTIVO.md) y la especificacion completa esta en [Logica de negocio y flujo del boceto](LOGICA_NEGOCIO_Y_FLUJO_BOCETO.md).

La distribucion de remuneracion descrita arriba esta implementada en el boceto
y en el sistema real. Migracion, modelos, servicios, validaciones de servidor,
snapshots, importacion y revision conservan los tres campos definidos.

## Verificacion automatizada actual

- `php artisan test`: 74 pruebas y 424 aserciones.
- `vendor/bin/pint --test`: correcto.
- `composer audit --locked`: pendiente; reporta 6 avisos en `league/commonmark` (<2.9.0), fuera del alcance de esta fase.
- `php artisan academico:verificar-normalizacion`: correcto.

## Pendiente para completar el flujo integral

La funcionalidad esta construida, pero todavia falta la validacion integral de aceptacion con usuarios y datos representativos. Esta es la condicion pendiente para declarar el flujo completo de Designaciones como operativo:

1. Ejecutar una prueba de punta a punta en PostgreSQL local con un Director y Vicerrectorado: crear borrador, asignar, importar, enviar, observar, corregir, reenviar y aprobar.
2. Repetir la prueba con dos carreras para comprobar el aislamiento de datos y con una fila aprobada previamente para confirmar su bloqueo.
3. Verificar desde navegador la lista de Designaciones, la bandeja de Revisiones, los filtros, la busqueda, los enlaces de notificaciones y los mensajes de validacion.
4. Registrar las observaciones de los usuarios responsables y resolverlas antes de habilitar el uso operativo con datos institucionales.
5. Definir por separado las reglas academicas que aun no estan aprobadas, como limites de carga horaria o formatos de reporte. El sistema no debe inventarlas ni bloquear por ellas.
6. Incorporar un visor de historial de snapshots dentro de la interfaz del boceto si se requiere paridad total con el sistema real.

## Criterio de cierre

El flujo se considerara completo cuando las pruebas anteriores pasen sin correcciones pendientes y los responsables confirmen que las pantallas y estados reflejan su proceso real.

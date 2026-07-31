# Estado del Flujo de Designaciones

## Implementado

- El Director trabaja en `/designaciones` y solo ve la informacion de su carrera.
- Cada propuesta abre una pantalla detallada de asignacion por docente; permite asignar por grupo, importar historicos, enviar o retirar la version pendiente y consultar el historial de revisiones.
- El borrador conserva filas aprobadas previamente como solo lectura.
- Cada envio crea una version inmutable y notifica a Vicerrectorado.
- Vicerrectorado usa `/revisiones/pendientes` para aprobar una revision completa o decidir por fila.
- Las observaciones devuelven el mismo borrador al Director; el reenvio crea una nueva version.
- La aprobacion final bloquea la propuesta y sus filas. Las notificaciones informan al Director y sus enlaces usan rutas vigentes.

## Pendiente para completar el flujo integral

La funcionalidad esta construida, pero todavia falta la validacion integral de aceptacion con usuarios y datos representativos. Esta es la condicion pendiente para declarar el flujo completo de Designaciones como operativo:

1. Ejecutar una prueba de punta a punta en PostgreSQL local con un Director y Vicerrectorado: crear borrador, asignar, importar, enviar, observar, corregir, reenviar y aprobar.
2. Repetir la prueba con dos carreras para comprobar el aislamiento de datos y con una fila aprobada previamente para confirmar su bloqueo.
3. Verificar desde navegador la lista de Designaciones, la bandeja de Revisiones, los filtros, la busqueda, los enlaces de notificaciones y los mensajes de validacion.
4. Registrar las observaciones de los usuarios responsables y resolverlas antes de habilitar el uso operativo con datos institucionales.
5. Definir por separado las reglas academicas que aun no estan aprobadas, como limites de carga horaria o formatos de reporte. El sistema no debe inventarlas ni bloquear por ellas.

## Criterio de cierre

El flujo se considerara completo cuando las pruebas anteriores pasen sin correcciones pendientes y los responsables confirmen que las pantallas y estados reflejan su proceso real.

# BUG-2026-10-02: La lectura de revisión bloqueaba decisiones independientes

Estado: **RESUELTO**
Severidad: Media
Ambiente: Interfaz de Vicerrectorado

## Precondiciones

- Una designación está disponible para revisión.
- La consulta de estados por fila o la consulta del estado general no está
  disponible.

## Reproducción

1. Abrir el detalle de una designación.
2. Hacer que falle la lectura de los estados de fila o del estado general.
3. Revisar las etiquetas y las acciones de aprobar/rechazar.

## Resultado esperado y actual

- Esperado: un fallo de lectura general no afecta las decisiones por fila; un
  fallo al leer estados de fila muestra `Sin estado` y permite intentar guardar
  una decisión. La revisión general también conserva sus acciones propias.
- Actual: el controlador compartía el mismo bloque `try/catch`; cualquier fallo
  vaciaba los estados, ocultaba la revisión y deshabilitaba las decisiones.

## Causa raíz

La vista usaba una única bandera de disponibilidad para consultas y escrituras
independientes. Así, una lectura de revisión fallida ocultaba información y
controles que no dependían de ella.

## Regresión y corrección

- `VicerrectoradoDesignacionesTest::test_el_fallo_al_consultar_revision_general_no_oculta_las_decisiones_por_fila`
- `VicerrectoradoDesignacionesTest::test_si_no_se_pueden_leer_estados_puede_guardar_y_muestra_sin_estado`
- Se separaron las lecturas y se conserva el estado desconocido como
  `Sin estado`; las escrituras se intentan por sus endpoints autorizados, que
  devuelven mensajes seguros si fallan.

## Riesgos y verificación

Si la lectura de fila no está disponible, los estados existentes no se pueden
mostrar hasta que la consulta vuelva a estar disponible. La escritura puede
fallar por separado y se informa sin exponer detalles internos.

- Pruebas dirigidas de Vicerrectorado: 16 pruebas, 172 aserciones, OK.
- Vista Blade, sintaxis PHP y sintaxis JavaScript: OK.
- El conjunto de pruebas que requiere el entorno PostgreSQL no pudo ejecutarse
  porque el servicio de pruebas no aceptó conexiones.

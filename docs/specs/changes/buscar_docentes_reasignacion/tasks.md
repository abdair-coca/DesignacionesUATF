# Tasks: Busqueda dual de docentes en reasignacion

## T-01 Servicio y consulta global

- Instrucciones: agregar catalogo por carrera y busqueda institucional con
  parametros enlazados, normalizacion y transaccion de solo lectura.
- Resultado esperado: ambas funciones se invocan sin escritura ni fuga tecnica.
- Estado: **COMPLETADO**

## T-02 Endpoint protegido

- Instrucciones: agregar ruta GET para el termino global, autorizada para
  `director_carrera`, con respuesta JSON segura.
- Resultado esperado: resultados validos, vacio para termino vacio y 503 seguro
  ante fallo externo.
- Estado: **COMPLETADO**

## T-03 Modal y formulario

- Instrucciones: usar catalogo de carrera para live search y agregar boton/Enter
  para resultados globales en el mismo campo.
- Resultado esperado: seleccionar un resultado global conserva el id y permite
  enviar la reasignacion.
- Estado: **COMPLETADO**

## T-04 Pruebas y cierre

- Instrucciones: agregar regresiones, ejecutar pruebas dirigidas y suite completa,
  y actualizar trazabilidad.
- Resultado esperado: spec y matriz reflejan la evidencia real.
- Estado: **COMPLETADO**

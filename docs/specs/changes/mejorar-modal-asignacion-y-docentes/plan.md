# Plan: Mejorar modal de asignacion y busqueda local de docentes

Referencia funcional: `spec.md`.
Estado: implementado y verificado el 17/09/2026; quedan fallos baseline fuera
del alcance documentados en `docs/testing/STATUS.md`.

## Fase 0 - Verificacion previa y contrato de identificadores

Objetivo: confirmar que el catalogo local puede alimentar el contrato de
escritura de Jachasun sin introducir un mapeo inventado.

Verificacion de salida:

- `App\Models\Docente` y `id_docente` usan el mismo espacio de identificadores,
  o existe una decision autorizada para resolver la diferencia.
- La spec queda aprobada por el usuario antes de tocar codigo.

## Fase 1 - Caracterizacion y regresiones

Objetivo: reproducir antes de corregir el error de actualizacion, la busqueda
incompleta, el envio de campos, la confirmacion innecesaria y el mensaje de
grupo.

Verificacion de salida:

- Cada fallo tiene una prueba independiente que falla con el comportamiento
  actual.
- No se debilitan las pruebas existentes de autorizacion, horas o grupos.

## Fase 2 - Busqueda local de docentes

Objetivo: reemplazar la dependencia del indice externo por una consulta Laravel
contra el catalogo local, conservando autorizacion, limite, normalizacion y
mensajes seguros.

Verificacion de salida:

- Nombre, apellido y CI funcionan con coincidencias parciales.
- Mayusculas, minusculas, tildes y espacios repetidos no alteran el resultado.
- La ruta no ejecuta `public.f_buscar_docente`.
- La respuesta solo contiene `id`, `nombre` y `ci`.
- Si `public.docentes` no existe en el despliegue, el servicio usa el catalogo
  local autorizado `academico.docentes` mediante un mapeo de lectura explícito.

## Fase 3 - Guardado y validacion dentro del modal

Objetivo: hacer que nueva fila y reasignacion envien todos los valores, validen
campos antes del POST y guarden directamente sin modal de confirmacion.

Verificacion de salida:

- Docente, materia, grupo y las tres horas llegan al endpoint.
- Los errores de campos incompletos permanecen dentro del modal.
- La confirmacion de cabecera no cambia.
- Un guardado valido deja de mostrar `No fue posible actualizar la
  asignacion.`.

## Fase 4 - Precarga y contexto del grupo

Objetivo: mostrar los valores actuales al reasignar y explicar visualmente el
grupo siguiente oficial al crear o editar.

Verificacion de salida:

- El modal de reasignacion abre con docente, materia, grupo y horas actuales.
- Una nueva fila usa el grupo `N + 1`.
- Una edicion conserva el grupo actual y permite el siguiente valido.
- El mensaje inferior al selector refleja si la materia ya tiene
  designaciones.
- La fuente de grupos sigue siendo oferta autorizada, aperturas y filas
  actuales; no se inventan grupos.

## Fase 5 - Integracion, seguridad y cierre

Objetivo: comprobar el flujo completo, conservar mensajes seguros y actualizar
la trazabilidad.

Verificacion de salida:

- Pasan las pruebas unitarias y feature relacionadas.
- Pasan `php -l`, `view:cache` y `vendor/bin/pint --test` en archivos tocados.
- Se ejecuta la suite completa y se registran fallos baseline sin ocultarlos.
- Se actualizan `docs/testing/STATUS.md`, `docs/testing/TEST_MATRIX.md` y los
  informes de regresion correspondientes.
- `spec.md` cambia de `Draft` a su estado real solo despues de verificar la
  implementacion.

## Archivos previstos

- `app/Services/Jachasun/JachasunDesignacionesService.php`
- `app/Http/Controllers/DesignacionController.php`
- `resources/views/designaciones/carrera.blade.php`
- `tests/Unit/JachasunDesignacionesServiceTest.php`
- `tests/Feature/JachasunDesignacionesDetailTest.php`
- `tests/Feature/JachasunDesignacionesEscrituraTest.php`
- `docs/testing/STATUS.md`
- `docs/testing/TEST_MATRIX.md`
- `docs/testing/BUG_REPORTS/`

La lista es orientativa para la implementacion; esta fase solo crea el plan y
las tareas, no modifica esos archivos.

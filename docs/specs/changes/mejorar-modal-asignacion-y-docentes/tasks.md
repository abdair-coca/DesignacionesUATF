# Tasks: Mejorar modal de asignacion y busqueda local de docentes

Orquestacion para worker. Referencias funcionales: `spec.md` y `plan.md`.
Todas las tareas deben ejecutarse en orden y verificarse antes de marcarse.

## T-00 Verificar contrato del catalogo local

- [x] Confirmar con datos de prueba o lectura autorizada que `docentes.id` es
  compatible con `id_docente` usado por `f_designacion_detalle`.
- [x] Verificar que no existe discrepancia entre identificadores; no fue necesario
  crear un mapeo ni registrar `NEEDS_BUSINESS_CONFIRMATION`.
- [x] Confirmar que la tabla local `docentes` contiene los docentes que deben
  poder seleccionarse en el modal.

## T-01 Reproducir el fallo de actualizacion

- [x] Crear una prueba feature que envie una reasignacion completa y reproduzca
  `No fue posible actualizar la asignacion.`.
- [x] Crear una prueba que verifique que el servicio recibe `id_detalle`,
  docente, materia, grupo y las tres horas.
- [x] Registrar la causa observada sin cambiar aun el controlador, servicio ni
  vista.

## T-02 Reproducir la busqueda incompleta

- [x] Crear pruebas con docentes locales que cubran nombre, apellido, CI,
  mayusculas, minusculas, tildes y espacios repetidos.
- [x] Crear una regresion que demuestre que un docente ausente del indice
  externo no puede encontrarse con el flujo actual.
- [x] Verificar que la prueba de autorizacion del director se conserva.

## T-03 Implementar busqueda local Laravel

- [x] Agregar el metodo de servicio Laravel para consultar `Docente` usando solo
  `id`, `nombre` y `ci`.
- [x] Normalizar el termino en Laravel, limitarlo a 100 caracteres y devolver
  vacio sin consulta cuando no haya termino.
- [x] Implementar coincidencia parcial por nombre completo y CI, tolerante a
  mayusculas/minusculas, tildes y espacios repetidos.
- [x] Aplicar limite y orden estable, eliminando duplicados.
- [x] Mantener el endpoint protegido y devolver solo el contrato JSON definido
  en `spec.md`.
- [x] Eliminar la dependencia de `public.f_buscar_docente` para este flujo sin
  crear un fallback externo.
- [x] Convertir errores de consulta en respuesta generica y registrar solo la
  clase de excepcion.
- [x] Ejecutar las pruebas unitarias del buscador y las pruebas feature JSON.

## T-04 Validar el formulario dentro del modal

- [x] Agregar un estado Alpine para errores de validacion de la fila y limpiarlo
  al abrir nueva fila o reasignacion.
- [x] Validar docente, materia, grupo, enteros no negativos y limites oficiales
  antes de enviar.
- [x] Rechazar localmente las tres horas en cero y permitir ceros individuales.
- [x] Mostrar los mensajes dentro del modal, sin redireccionar.
- [x] Cubrir cada campo faltante y el caso de horas todas en cero con pruebas.

## T-05 Eliminar confirmacion solo para filas

- [x] Cambiar Guardar de nueva fila/reasignacion para enviar directamente el
  formulario cuando la validacion local sea correcta.
- [x] Mantener intacta la confirmacion de edicion de cabecera.
- [x] Verificar que no se abre `modal-confirmacion` al guardar una fila.
- [x] Cubrir nueva fila y reasignacion con pruebas de vista/interaccion.

## T-06 Corregir el envio y el error de actualizacion

- [x] Verificar que los campos visibles de docente, materia, grupo y horas
  pertenecen al formulario real o se asocian explicitamente a el.
- [x] Verificar que un guardado valido llega al controlador con los siete campos
  esperados.
- [x] Corregir la causa del mensaje generico sin exponer detalles tecnicos.
- [x] Mantener autorizacion por carrera y validacion server-side.
- [x] Ejecutar la regresion del error de actualizacion y la prueba de guardado
  exitoso.

## T-07 Precargar reasignacion

- [x] Al abrir una fila existente, cargar docente, CI, materia, grupo y horas
  actuales en el estado Alpine.
- [x] Mostrar esos valores en los controles visibles del modal.
- [x] Evitar reemplazar grupo u horas actuales al abrir o cerrar el modal.
- [x] Verificar que cambiar materia carga las horas oficiales y el grupo
  siguiente sin romper la conservacion de una edicion sin cambios.
- [x] Agregar pruebas de renderizado y de estado inicial.

## T-08 Mostrar grupo siguiente y aviso contextual

- [x] Exponer al modal el grupo siguiente calculado por la fuente oficial,
  aperturas y filas actuales.
- [x] En nueva fila, seleccionar y mostrar solo el grupo siguiente valido.
- [x] En edicion, conservar el grupo actual y mostrar el siguiente como opcion
  adicional cuando corresponda.
- [x] Mostrar debajo del selector el aviso de materia con designaciones y el
  numero `N + 1`.
- [x] Ocultar o actualizar el aviso al cambiar de materia.
- [x] Mantener el modo seguro `maximo asignado + 1` cuando la fuente autorizada
  no este disponible, sin inventar una lista de grupos.
- [x] Agregar pruebas para materia sin designaciones, grupo 1, grupo N,
  edicion, nueva fila y fuente degradada.

## T-09 Integracion y seguridad

- [x] Ejecutar las suites unitarias y feature relacionadas despues de cada
  cambio funcional.
- [x] Verificar que no se exponen proveedores, funciones, SQL, credenciales ni
  detalles de infraestructura en mensajes visibles.
- [x] Ejecutar `php -l` en PHP modificado.
- [x] Ejecutar `php artisan view:cache --no-ansi`.
- [x] Ejecutar `vendor/bin/pint --test` en archivos modificados.
- [x] Ejecutar la suite completa y separar fallos nuevos de fallos baseline.

## T-10 Cierre documental

- [x] Crear o actualizar un informe en `docs/testing/BUG_REPORTS/` por cada bug
  reproducido y corregido.
- [x] Actualizar `docs/testing/STATUS.md` con archivos, comandos, resultados,
  fallos y riesgos.
- [x] Actualizar `docs/testing/TEST_MATRIX.md` con los casos cubiertos.
- [x] Registrar la verificacion de compatibilidad entre IDs locales y Jachasun.
- [x] Cambiar el estado de `spec.md` solo despues de completar las pruebas y
  cerrar los riesgos de la fase.

## T-11 Corregir el catalogo del despliegue

- [x] Verificar sin exponer datos que `public.docentes` no existe y que
  `academico.docentes` es la fuente local disponible.
- [x] Crear una regresion para el esquema academico y sus columnas publicas.
- [x] Implementar el uso de `academico.docentes` solo cuando falte la tabla
  publica, sin volver a la funcion de indice externo.
- [x] Verificar que la respuesta no expone columnas sensibles.
- [x] Ejecutar un smoke de lectura real y registrar sus resultados.

## T-12 Corregir escritura sin tabla auxiliar

- [x] Reproducir la excepción con la tabla `designacion_grupo_aperturas` ausente.
- [x] Crear una regresion que permita continuar con la funcion de escritura
  cuando la tabla auxiliar no esta disponible.
- [x] Conservar la apertura local cuando la tabla auxiliar si existe.
- [x] Verificar que no se ejecutan escrituras reales durante las pruebas.
- [x] Registrar el bug, la causa y la evidencia en `docs/testing/`.

## T-13 Conservar materia actual al reasignar

- [x] Reproducir una fila cuya materia no aparece en la oferta vigente.
- [x] Crear una regresion que verifique que la materia actual se conserva en el
  modal de edicion.
- [x] Marcar la materia agregada como `solo_edicion` para no ofrecerla en una
  nueva designacion.
- [x] Mantener el calculo de disponibilidad de grupos basado solo en la oferta
  autorizada.
- [x] Registrar el bug, la causa, las pruebas y el riesgo de verificacion manual.

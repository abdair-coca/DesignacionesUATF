# Spec: Mejorar modal de asignacion y busqueda local de docentes

Status: Implemented - verified 2026-09-17; baseline failures documented
Deciders: Dueno del sistema de designaciones
Date: 2026-09-17
Scope: Modal de nueva asignacion/reasignacion, busqueda local de docentes y
guardado de filas de detalle. No incluye cambios en la edicion de la cabecera
de la designacion.

No se crea `proposal.md` por solicitud expresa. Esta especificacion fue
verificada antes de implementar.

## Problemas

1. Una reasignacion valida termina mostrando `No fue posible actualizar la
   asignacion.` y no deja evidencia suficiente de que los valores seleccionados
   llegaron al guardado.
2. La busqueda actual de docentes depende de un indice/funcion externa y no
   encuentra algunos nombres o apellidos.
3. El modal no explica visualmente que una materia ya tiene designaciones y que
   se esta usando el siguiente grupo consecutivo.
4. El modal usa confirmacion adicional para guardar una fila de docente.
5. Al pulsar Guardar con campos incompletos, la validacion ocurre despues del
   envio y el usuario pierde el contexto del modal.
6. Al reasignar, los valores de la fila seleccionada deben quedar visibles y
   editables desde la apertura del modal.

## Objetivos

- Hacer confiable el guardado de filas nuevas y reasignadas.
- Buscar docentes en Laravel usando el catalogo local `App\Models\Docente`,
  sin depender de `public.f_buscar_docente` ni de un indice externo.
- Permitir busqueda por nombre completo, apellido o CI, con coincidencia
  parcial, insensible a mayusculas/minusculas y tolerante a tildes.
- Mostrar el grupo siguiente oficial en el modal y explicar su origen.
- Validar los datos incompletos dentro del modal antes de enviar.
- Mantener las validaciones de autorizacion y del servidor como ultima barrera.

## Fuera de alcance

- No cambiar la edicion de la cabecera, observacion o fecha de la designacion.
- No eliminar la confirmacion de la edicion de cabecera.
- No cambiar las funciones de Jachasun ni crear una nueva funcion de BD en esta
  fase, salvo que la implementacion posterior demuestre que el contrato actual
  no puede guardar una fila valida.
- No modificar la regla de horas: cada hora puede ser `0`, pero las tres no
  pueden ser `0` al mismo tiempo.
- No inventar grupos ni mostrar una lista completa no autorizada.

## Solucion funcional

### 1. Guardado de nueva fila y reasignacion

- El boton Guardar de la fila debe enviar directamente el formulario, sin abrir
  `modal-confirmacion`.
- La confirmacion de cabecera permanece sin cambios.
- Antes de enviar, el modal debe validar localmente:
  - docente seleccionado;
  - materia seleccionada;
  - grupo seleccionado;
  - horas enteras y no negativas;
  - al menos una hora mayor que `0`;
  - ninguna hora superior a la hora oficial de la materia.
- Si falta un dato, el modal permanece abierto y muestra un mensaje formal
  debajo del formulario o junto al campo correspondiente. No se debe redirigir
  ni mostrar el error despues del POST.
- La validacion local no reemplaza la validacion del controlador ni del
  servicio.
- Un guardado valido debe conservar en el request los valores de docente,
  materia, grupo y las tres horas, tanto para `id_detalle = 0` como para una
  reasignacion existente.
- Si el servidor rechaza una operacion valida por una causa externa, se debe
  conservar un mensaje visible generico y registrar de forma controlada la
  clase de excepcion, sin exponer proveedores, SQL, credenciales o detalles de
  infraestructura.
- El caso que actualmente muestra `No fue posible actualizar la asignacion.`
  debe tener una prueba de regresion que reproduzca el fallo y una prueba de
  guardado exitoso con los parametros completos.

### 2. Datos precargados al reasignar

Al abrir el modal desde una fila existente, deben cargarse en la misma
operacion de apertura:

- Docente: nombre y CI visibles, con el identificador conservado para el
  formulario.
- Materia: sigla y nombre visibles, con el identificador conservado.
- Grupo: grupo actual seleccionado.
- Horas: valores actuales de teóricas, practicas y laboratorio.

La seleccion de una materia distinta puede cargar sus horas oficiales y su
grupo siguiente. Si se abre una fila existente sin cambiar materia, no se deben
reemplazar silenciosamente sus horas editadas ni su grupo actual.

### 3. Busqueda local de docentes

- La ruta protegida del buscador debe consultar el modelo local `Docente` o un
  servicio Laravel que lo encapsule.
- Cuando el despliegue no tenga `public.docentes`, el servicio puede usar el
  catalogo local autorizado `academico.docentes`, mapeando `id_docente` y la
  composicion de nombres al contrato sin leer ni devolver columnas sensibles.
- La consulta debe usar solo las columnas necesarias: `id`, `nombre` y `ci`.
- El termino debe limitarse a 100 caracteres, recortarse y normalizarse en
  Laravel antes de consultar.
- La coincidencia debe funcionar por cualquier parte de `nombre` o `ci`.
  Como el catalogo local guarda el nombre completo en `nombre`, eso incluye
  nombre, apellido paterno y apellido materno cuando esten registrados en el
  mismo campo.
- La comparacion debe ignorar mayusculas/minusculas, espacios repetidos y
  tildes. No se debe usar `tsquery`, ranking externo ni depender de un indice
  incompleto.
- Los resultados deben ser unicos por docente, ordenados de forma estable y
  limitados a un maximo definido por la implementacion para evitar respuestas
  excesivas.
- El endpoint no debe devolver contrasenas, correo, carrera completa ni otros
  atributos del modelo.
- La autorizacion del director de carrera se mantiene antes de consultar el
  catalogo.
- Un termino vacio devuelve una coleccion vacia sin consulta costosa.
- Un error de consulta devuelve un mensaje generico y seguro; los detalles solo
  se registran de forma controlada.
- El campo de materia debe seguir funcionando antes o despues de seleccionar
  docente.

### 4. Grupo siguiente visible

Debajo del selector de grupo se debe mostrar una ayuda contextual cuando la
materia seleccionada ya tenga una o mas designaciones:

- Nueva fila: `La materia ya tiene designaciones. Se asignara el grupo N + 1:`
  seguido del numero siguiente calculado.
- Edicion de una fila que conserva su grupo: `La materia ya tiene
  designaciones. Se conserva el grupo actual y el siguiente grupo disponible
  es N + 1.`
- Materia sin designaciones: no se muestra el aviso de materia existente; se
  muestra solamente el grupo siguiente oficial cuando corresponda.
- El aviso debe usar las filas actuales, las aperturas registradas y la oferta
  autorizada. No debe enumerar grupos no autorizados.
- El grupo siguiente debe coincidir con la regla vigente: si existe el grupo
  `1`, una nueva fila usa el grupo `2`; si existe `N`, usa `N + 1`.
- Al editar se conserva el grupo actual como opcion valida. Un grupo nuevo debe
  cumplir la misma secuencia.
- Si la fuente de grupos no esta disponible, el siguiente se deriva unicamente
  de las filas actuales (`maximo asignado + 1`) y no se inventan alternativas.
- La tabla auxiliar local `designacion_grupo_aperturas` se usa solo cuando esta
  disponible; su ausencia no debe impedir la escritura autorizada en Jachasun.

## Contratos de datos

### Busqueda de docentes

Entrada:

```text
GET /designaciones/docentes/buscar?q={termino}
```

Respuesta exitosa, solo campos necesarios:

```json
[
  {"id": 123, "nombre": "Nombre Apellido", "ci": "1234567"}
]
```

El identificador solo se entrega porque es necesario para conservar la
seleccion del formulario; no se muestra como texto independiente en la
interfaz.

### Guardado de detalle

El formulario debe enviar siempre:

```text
id_detalle
id_docente
id_materia
id_grupo
hrs_teoria
hrs_practica
hrs_laboratorio
```

Los mensajes de validacion del navegador deben ser formales y no tecnicos.
Los mensajes de excepcion del servidor permanecen genericos.

## Invariantes

- Solo un director autorizado puede buscar y guardar desde el flujo de su
  carrera.
- El programa/carrera usado para guardar proviene del usuario autenticado y no
  de un valor confiado del navegador.
- El docente seleccionado debe ser un docente valido para el contrato de
  escritura de la designacion.
- La materia, el grupo y las horas deben pertenecer al contexto academico
  autorizado.
- Una nueva fila usa el grupo siguiente; una edicion puede conservar el grupo
  actual.
- Las tres horas no pueden ser simultaneamente `0`.
- No se ejecuta una escritura si falla la validacion local del modal.
- La validacion servidor sigue siendo obligatoria para evitar manipulacion del
  navegador.
- Ningun error visible expone nombres de funciones, proveedores, consultas,
  credenciales o nombres internos de infraestructura.

## Casos de prueba requeridos

### Guardado y modal

- Abrir reasignacion y verificar que docente, materia, grupo y horas aparecen
  con los valores de la fila elegida.
- Modificar docente, materia, grupo y horas; verificar que todos llegan al
  servicio con los nombres de campo esperados.
- Abrir nueva fila y guardar con todos los campos validos sin mostrar modal de
  confirmacion.
- Pulsar Guardar sin docente: el modal permanece abierto y muestra validacion.
- Pulsar Guardar sin materia: el modal permanece abierto y muestra validacion.
- Pulsar Guardar sin grupo: el modal permanece abierto y muestra validacion.
- Pulsar Guardar con las tres horas en `0`: el modal permanece abierto y
  muestra validacion.
- Guardar una hora en `0` y otra mayor que `0`: se permite continuar.
- Reproducir el fallo `No fue posible actualizar la asignacion.` antes del fix y
  verificar el guardado exitoso despues del fix.

### Busqueda local

- Buscar por nombre parcial.
- Buscar por apellido parcial.
- Buscar por CI parcial y completo.
- Buscar con mayusculas, minusculas, tildes y sin tildes.
- Buscar con espacios repetidos y signos no peligrosos.
- Buscar termino vacio sin consulta costosa.
- Verificar limite de resultados, orden estable y ausencia de duplicados.
- Verificar que no se consulta `public.f_buscar_docente` ni otra funcion de
  indice externo.
- Verificar autorizacion y mensaje seguro ante error local.

### Grupo siguiente

- Materia sin designaciones: muestra el primer grupo oficial.
- Materia con grupo `1`: muestra `2` y el aviso de siguiente grupo.
- Materia con grupos hasta `N`: muestra `N + 1`.
- Nueva fila no permite guardar un grupo distinto al siguiente.
- Edicion conserva el grupo actual y permite el siguiente valido.
- Fuente de grupos no disponible: deriva solo `maximo + 1` de filas actuales.
- El aviso desaparece o cambia correctamente al cambiar de materia.

## Riesgos y dependencias

- El catalogo local `docentes` debe contener los docentes que pueden guardarse
  en Jachasun. El usuario confirmo que `docentes.id` usa el mismo espacio de
  identificadores que `id_docente` de la funcion de escritura.
- El catalogo local puede estar desactualizado respecto a docentes
  institucionales. La spec no autoriza sincronizar datos ni consultar un
  proveedor externo como fallback.
- El grupo siguiente depende de la oferta autorizada, aperturas y filas
  actuales. No se debe reemplazar por un rango inventado en el navegador.
- `designacion_grupo_aperturas` puede no existir en el despliegue Jachasun; el
  servicio no debe intentar escribirla cuando no esta disponible.
- La eliminacion de la confirmacion solo aplica a filas de docente; la
  confirmacion de cabecera no cambia.
- La suite debe agregar regresiones antes de corregir los fallos y conservar
  pruebas independientes.

## Dependencias tecnicas

- `App\Models\Docente` y, en el despliegue Jachasun, el catalogo local
  `academico.docentes` (`id_docente`, `nombres`, `paterno`, `materno`, `ci`).
- `DesignacionController`, `JachasunDesignacionesService` y
  `resources/views/designaciones/carrera.blade.php`.
- Endpoint protegido `designaciones.docentes.buscar`.
- Contrato actual de `designaciones.f_designacion_detalle` y sus validaciones de
  carrera, materia, grupo y horas.

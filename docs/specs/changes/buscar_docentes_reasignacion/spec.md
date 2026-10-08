# Spec: Busqueda dual de docentes en reasignacion

Status: Done
Deciders: Dueno del sistema de designaciones
Date: 2026-09-11

## Solucion

- El servicio cargara el catalogo de la carrera con `f_lista_docentes` en
  transaccion `READ ONLY`.
- Una ruta GET protegida ejecutara `f_buscar_docente` con el termino validado y
  devolvera solo los campos necesarios para el selector.
- El input del modal filtrara localmente mientras se escribe.
- El boton a la derecha y la tecla Enter ejecutaran la busqueda institucional y
  reemplazaran las opciones del desplegable por sus resultados.
- La seleccion conservara el id del docente en el formulario existente de
  reasignacion.

## Invariantes

- El live search nunca consulta fuera del catalogo de la carrera.
- La busqueda global no escribe en BD y no expone errores tecnicos.
- La autorizacion del director y las validaciones actuales de materia, grupo y
  horas permanecen activas.
- El docente global seleccionado se envia al mismo endpoint de reasignacion.

## Test cases

- `f_lista_docentes` se consulta con la sigla de la carrera y normaliza filas.
- `f_buscar_docente` responde JSON con resultados y consulta `READ ONLY`.
- Boton y Enter ejecutan el submit search.
- Es posible seleccionar un resultado global y enviarlo al formulario.
- Invitados, otros roles y errores de BD reciben respuestas seguras.

## Riesgos

- La funcion global puede fallar con signos reservados de `tsquery`; el servicio
  limita la entrada y convierte el fallo en mensaje seguro.

## Dependencias

- `academico.f_lista_docentes` y `public.f_buscar_docente` en Jachasun.
- `JachasunDesignacionesService`, `DesignacionController` y el modal existente.

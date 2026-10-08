# Busqueda dual de docentes en reasignacion

Status: Accepted
Deciders: Dueno del sistema de designaciones
Date: 2026-09-11

## Contexto y problema

El modal de reasignacion solo permite filtrar los docentes ya presentes en la
designacion. Se necesitan dos busquedas en el mismo campo: una local para la
carrera y otra institucional para encontrar cualquier docente autorizado.

## Goals

- Filtrar en vivo los docentes de la carrera.
- Buscar en toda la universidad mediante un boton o Enter.
- Permitir seleccionar y guardar un resultado institucional.

## Non-Goals

- Cambiar las reglas de materia, grupo u horas.
- Crear nuevos docentes o modificar las funciones de BD.

## Reglas de negocio

- El live search usa `academico.f_lista_docentes(sigla)`.
- El submit search usa `public.f_buscar_docente(texto)`.
- Un docente encontrado globalmente puede guardarse en la reasignacion.
- Solo `director_carrera` puede consultar y guardar desde el modal.

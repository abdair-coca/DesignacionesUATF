# Specs — Flujo SDD (Spec-Driven Development)

Directorio para el desarrollo guiado por especificaciones. Cada feature nueva se
diseña y planifica aquí **antes** de escribir código, y el código debe cumplir lo
especificado.

## Estructura

```
docs/specs/
  README.md           ← este protocolo
  changes/
    <feature>/        ← una carpeta por feature, nombre en snake_case
      proposal.md     ← qué y por qué
      spec.md         ← cómo (solución detallada, invariantes, tests)
      plan.md         ← fases de implementación y orden de ejecución
      tasks.md        ← desglose en tareas atómicas para orquestación
```

Para crear una feature, copiar las plantillas de la sección [Plantillas](#plantillas)
a `docs/specs/changes/<feature>/` y rellenarlas.

## Estados de un spec

Cada spec indica su estado en la línea `Status:` del encabezado:

| Estado | Significado |
|---|---|
| `Proposed` | Idea planteada; falta validación de negocio. |
| `In Progress` | Aprobada; se está implementando. |
| `Done` | Implementada y verificada. |

## Ciclo de trabajo

1. **Proposal**: escribir `proposal.md` con el contexto y problema. Validar reglas de
   negocio antes de avanzar.
2. **Spec**: escribir `spec.md` con la solución, invariantes y casos de prueba.
3. **Plan**: desglosar en fases en `plan.md` y en tareas atómicas en `tasks.md`.
4. **Implementar**: seguir `plan.md`/`tasks.md`; el código debe satisfacer `spec.md`.
5. **Verificar**: ejecutar las pruebas de la suite (ver `docs/README.md`); una feature
   no pasa a `Done` sin su verificación registrada.
6. Al cerrar, marcar `Status: Done` y registrar en `docs/testing/STATUS.md` y
   `docs/testing/TEST_MATRIX.md`.

## Reglas

- **No inventar reglas universitarias.** Ante cualquier duda o ambigüedad de negocio,
  detenerse y preguntar; marcar la regla como `NEEDS_BUSINESS_CONFIRMATION` en el spec
  hasta que sea confirmada.
- No mover a `In Progress` una feature con ambigüedades de negocio sin confirmar.
- Las invariantes de `spec.md` son vinculantes; si una implementación las rompe, es un
  bug.
- No eliminar, omitir ni debilitar pruebas para conseguir resultados verdes.
- Los specs describen la solución y sus límites; no incluir prompts, trazas de
  conversación ni instrucciones exclusivas de asistentes.

## Plantillas

### proposal.md

```markdown
# <Feature>

Status: Proposed
Deciders: <rol o persona que decide>
Date: AAAA-MM-DD

## Contexto y problema

<Qué situación actual motiva la feature y qué problema resuelve.>

## Goals

- <Objetivo medible o verificable>

## Non-Goals

- <Lo que esta feature NO cubre>

## Alternativas consideradas

- <Opción A — por qué se descarta>
- <Opción B — por qué se elige>

## Reglas de negocio

- <Regla confirmada o marcada NEEDS_BUSINESS_CONFIRMATION>
```

### spec.md

```markdown
# Spec: <Feature>

Status: <Proposed | In Progress | Done>
Deciders: <rol o persona que decide>
Date: AAAA-MM-DD

## Solución

<Descripción detallada: entidades, flujo, contratos, cambios de esquema.>

## Invariantes

- <Propiedad que siempre debe cumplirse>

## Test cases

- <Caso: entrada → resultado esperado>

## Riesgos

- <Riesgo y mitigación>

## Dependencias

- <Otros specs, fases o sistemas>
```

### plan.md

```markdown
# Plan: <Feature>

## Fases

### Fase 1 — <nombre>
- [ ] <paso>
- [ ] <paso>
- Verificación: <comando o prueba>

### Fase 2 — <nombre>
- [ ] <paso>
- [ ] <paso>
- Verificación: <comando o prueba>

## Archivos afectados

- <ruta/archivo>
```

### tasks.md

```markdown
# Tasks: <Feature>

Orquestación para worker. Ver protocolo en `docs/tasks/README.md`.

## T-01 <título>
- Instrucciones: <qué hacer exactamente>
- Archivos: <rutas>
- Resultado esperado: <aceptación>

## T-02 <título>
- Instrucciones: <qué hacer exactamente>
- Archivos: <rutas>
- Resultado esperado: <aceptación>
```
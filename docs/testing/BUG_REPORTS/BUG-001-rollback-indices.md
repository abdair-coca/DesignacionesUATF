# BUG-001: rollback de índices fallaba en la Fase 0

## Severidad

P2 — importante para la infraestructura de pruebas.

## Módulo

Migraciones de base de datos.

## Ambiente

PostgreSQL local, base `designaciones_uatf_testing`, puerto `55432`.

## Precondiciones

Base vacía y código previo a la corrección.

## Pasos para reproducir

1. Ejecutar `composer test:phase0`.
2. Esperar la fase de rollback.

## Resultado actual antes de corregir

El rollback fallaba en `2026_07_15_000001_add_missing_indexes` con el error de
índice inexistente `Id_docente`.

## Resultado esperado

Las 34 migraciones deben aplicarse desde cero, revertirse y volver a aplicarse.

## Causa raíz

`dropIndex()` recibía nombres de columnas como si fueran nombres explícitos de
índice. Laravel genera nombres como `designaciones_id_docente_index`.

## Prueba de regresión

`composer test:phase0` verifica fresh, rollback completo y migración final.

## Corrección

Se cambió a `$table->dropIndex(['Id_docente'])` y
`$table->dropIndex(['Id_grupo'])`.

## Estado

**CERRADO** — verificado el 2026-08-04.

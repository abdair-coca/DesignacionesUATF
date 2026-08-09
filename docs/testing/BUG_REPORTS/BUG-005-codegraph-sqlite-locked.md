# BUG-005: CodeGraph no puede abrir su indice SQLite

## Severidad

P2 - herramienta de analisis local. No afecta la ejecucion de Laravel ni la
base PostgreSQL de la aplicacion.

## Ambiente

- Windows local.
- Proyecto `DesginacionesUATF`.
- Indice `.codegraph/codegraph.db` presente.
- Consulta MCP `codegraph_status`.

## Reproduccion

1. Ejecutar `codegraph_status` apuntando al proyecto.
2. Recibir `database is locked`.
3. Archivar el directorio stale `.codegraph/codegraph.db.lock` con un nombre
   recuperable.
4. Repetir `codegraph_status`.

## Resultado esperado

CodeGraph informa el estado del indice y permite consultas estructurales.

## Resultado actual

El servidor MCP continua devolviendo `database is locked` incluso despues de
archivar el lock stale. No se elimino `codegraph.db` ni se modifico su contenido.

## Accion realizada

- Se movio el directorio lock a `.codegraph/codegraph.db.lock.stale-20260809`.
- Se conservaron los locks stale anteriores.
- No se borro la base ni se reinicializo el indice.

## Siguiente accion

Reiniciar el servicio MCP/CodeGraph desde el entorno que lo administra o
identificar el proceso que conserva el descriptor SQLite. Solo despues se debe
reintentar `codegraph_status`. Si se requiere reconstruir el indice, hacerlo
como operacion separada y conservando una copia del archivo actual.

## Estado

ABIERTO. Sin prueba de regresion porque el fallo pertenece al servicio auxiliar,
no al codigo de la aplicacion.

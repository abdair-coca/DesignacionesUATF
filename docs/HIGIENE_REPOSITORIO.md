# Higiene y organización del repositorio

Este repositorio debe publicar solo codigo fuente, configuracion reproducible, pruebas y documentacion tecnica estable del sistema.

## Material histórico

Los planes, bocetos, bitácoras, mapas y reportes cerrados se conservan en
[`archive/`](archive/) para no perder contexto. No deben enlazarse como reglas
vigentes ni duplicarse en nuevos documentos.

## No versionar

Los siguientes archivos y carpetas son material local de trabajo y no deben subirse a GitHub:

- Configuración local de asistentes: `opencode.md`, `.claude/`, `.gemini/`.
  `AGENTS.md` es la única fuente de instrucciones del agente.
- Tareas temporales, handoffs o coordinación entre sesiones nuevos: `docs/tasks/`.
- Bitácoras operativas de sesiones nuevas: no crear otra `docs/bitacora/`.
- Planes internos de mejora o prompts de trabajo nuevos: no agregarlos fuera de
  `docs/archive/`.
- Salidas regenerables de analisis: `graphify-out/`.
- Credenciales, entornos locales, caches y dependencias instaladas: `.env`, `.env.testing`, `vendor/`, `node_modules/`, `.phpunit.result.cache`.

Estos elementos pueden existir en la maquina local, pero deben permanecer fuera del indice de Git.

## Antes de confirmar cambios

Ejecutar estas revisiones:

```bash
git status --short
git diff --check
```

Si aparece un archivo auxiliar ya rastreado por error, retirarlo del indice sin borrarlo localmente:

```bash
git rm --cached <ruta>
```

Para carpetas completas:

```bash
git rm -r --cached <ruta>
```

Despues de retirarlo del indice, confirmar que la ruta este cubierta por `.gitignore`.

## Documentación vigente

La entrada principal es `docs/README.md`. Mantener solo referencias técnicas
que aporten información distinta:

- `README.md`: resumen y comandos básicos.
- `docs/README.md`: contexto, flujo vigente y mapa del código.
- `docs/INTEGRATION_JACHASUN.md`: contrato de integración.
- `docs/HIGIENE_REPOSITORIO.md`: estas reglas.
- `docs/specs/`: especificaciones de cambios.
- `docs/testing/STATUS.md`, `docs/testing/TEST_MATRIX.md` y
  `docs/testing/BUG_REPORTS/`: trazabilidad obligatoria.

El material histórico vive en `docs/archive/` y no debe enlazarse como regla
vigente. Evite duplicar el contexto del proyecto en nuevas guías. Cualquier
documento nuevo debe aportar una referencia técnica estable; no debe incluir
prompts, trazas de conversación ni instrucciones exclusivas para asistentes.
Las decisiones universitarias no confirmadas se marcan
`NEEDS_BUSINESS_CONFIRMATION`.

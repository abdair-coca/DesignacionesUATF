# Higiene y organización del repositorio

Este repositorio debe publicar solo codigo fuente, configuracion reproducible, pruebas y documentacion tecnica estable del sistema.

## Material histórico

Los planes, bocetos, bitácoras, mapas y reportes cerrados se conservan en
[`archive/`](archive/) para no perder contexto. No deben enlazarse como reglas
vigentes ni duplicarse en nuevos documentos.

## No versionar

Los siguientes archivos y carpetas son material local de trabajo y no deben subirse a GitHub:

- Contexto, instrucciones o memoria de asistentes: `CLAUDE.md`, `opencode.md`, `.claude/`, `.gemini/`.
- Tareas temporales, handoffs o coordinación entre sesiones nuevos: `tasks/`.
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

## Documentacion vigente aceptada

Se considera publicable:

- `README.md`
- `docs/ARCHITECTURE.md`
- `docs/BUSINESS_RULES.md`
- `docs/OPERATIONS.md`
- `docs/TESTING.md`
- `docs/INTEGRATION_JACHASUN.md`
- `docs/HIGIENE_REPOSITORIO.md`

También son obligatorios para trazabilidad `docs/testing/STATUS.md`,
`docs/testing/TEST_MATRIX.md` y `docs/testing/BUG_REPORTS/`.

Cualquier nuevo documento debe describir arquitectura, instalación, pruebas,
decisiones técnicas estables o uso del sistema. No debe incluir prompts,
trazas de conversación, notas de herramientas ni instrucciones exclusivas para
asistentes. Las reglas no confirmadas deben marcarse
`NEEDS_BUSINESS_CONFIRMATION`.

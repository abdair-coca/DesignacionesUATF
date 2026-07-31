# Higiene del repositorio

Este repositorio debe publicar solo codigo fuente, configuracion reproducible, pruebas y documentacion tecnica estable del sistema.

## No versionar

Los siguientes archivos y carpetas son material local de trabajo y no deben subirse a GitHub:

- Contexto, instrucciones o memoria de asistentes: `CLAUDE.md`, `opencode.md`, `.claude/`, `.gemini/`.
- Tareas temporales, handoffs o coordinacion entre sesiones: `tasks/`, `docs/TwinsTasks/`.
- Bitacoras operativas de sesiones: `docs/bitacora/`.
- Planes internos de mejora o prompts de trabajo: `docs/PLAN_ELEVACION_PROYECTO.md`.
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

## Documentacion publica aceptada

Se considera publicable:

- `README.md`
- `docs/ERD.md`
- `docs/ESTADO_FLUJO_DESIGNACIONES.md`
- `docs/PLAN_FRONTEND.md`
- `docs/HIGIENE_REPOSITORIO.md`

Cualquier nuevo documento debe describir arquitectura, instalacion, pruebas, decisiones tecnicas estables o uso del sistema. No debe incluir prompts, trazas de conversacion, notas de herramientas ni instrucciones exclusivas para asistentes.

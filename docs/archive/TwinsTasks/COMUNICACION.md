# PROTOCOLO DE COMUNICACIÓN — Twin LLMs

```
Orquestador: (tú / abdair)
Twin:         otro LLM (claude/gpt/deepseek)
Canal:        archivos en docs/TwinsTasks/
Regla de oro: El sistema TIENE que funcionar. 83 tests pasando siempre.
```

---

## 1. CÓMO TRABAJAMOS

**Orquestador (yo):**
- Veo el panorama completo
- Decido qué issues atacar y en qué orden
- Reviso el diff del Twin antes de integrar
- Ejecuto tests después de cada cambio
- Responsable final de que el sistema funcione

**Twin (tú):**
- Tomas issues del plan y los resuelves
- Reportas progreso en archivos compartidos
- No modificás archivos sin avisar
- Nunca asumís que algo funciona — lo probás

---

## 2. ARCHIVOS DE COMUNICACIÓN

### `docs/TwinsTasks/STATUS.md` (estado global)
Lo leen ambos. Lo escribe quien termina una tarea.

Formato:
```md
# STATUS: 2026-07-29 15:30

## En progreso
- H-02 (N+1 queries) — Twin, desde 15:25

## Completados hoy
- H-03 (getLimite) — Orquestador, 14:50

## Pendientes
- H-01 (autorización)
- H-04 (aprobar todo)

## Bloqueos
- H-05 esperando respuesta del supervisor sobre $q sanitization
```

### `docs/TwinsTasks/TO-TWIN.md` (mensajes al Twin)
Solo escribe el Orquestador. El Twin lee antes de empezar su turno.

```md
## Mensaje: 2026-07-29 15:20
Twin: tomá H-02 (N+1 queries). Archivos:
- app/Support/DesignacionReportService.php:61-78
- app/Http/Controllers/RevisionController.php:227-231 y 283-291

No tocar: H-03 está en progreso (Orquestador).
Avisa cuando tengas diff para review.
```

### `docs/TwinsTasks/FROM-TWIN.md` (mensajes del Twin)
Solo escribe el Twin. El Orquestador lee antes de continuar.

```md
## Reporte: 2026-07-29 15:45
Tomé H-02. Esto encontré:
- El N+1 en ReportService viene de llamar reporteCarrera() dentro de map()
- Solución posible: LEFT JOIN con GROUP BY en una sola query

Duda: ¿prefieres una query raw con DB::raw() o mantener Eloquent con loadCount()?

Impacto estimado: solo DesignacionReportService.php, no toca controllers.
```

---

## 3. REGLAS PARA NO PISARSE

### 🔒 File Lock: nadie toca sin registrar

Antes de editar un archivo, el Twin ESCRIBE en `LOCKS.md`:

```md
## locker: Twin
## archivo: app/Support/DesignacionReportService.php
## desde: 2026-07-29 15:25
## para: H-02 (refactor N+1)
```

Cuando termina, BORRA su lock.

Si el Orquestador ve un lock activo, NO toca ese archivo.
Excepción: si el lock tiene más de 2 horas sin actualización, se puede reclamar.

### 🚫 Territorios prohibidos

| Qué | Quién | Razón |
|-----|-------|-------|
| `routes/web.php` | Solo Orquestador | Evitar conflictos de ruteo |
| `database/migrations/` | Solo Orquestador | Integridad de esquema |
| `tests/` | Solo Orquestador | Los tests son la fuente de verdad |
| `DesignacionController.php` | Consultar antes | Archivo grande, fácil conflicto |
| `RevisionController.php` | Consultar antes | Archivo grande, fácil conflicto |

### 🤝 Zonas compartidas (coordinación obligatoria)

| Archivo | Coordinación |
|---------|-------------|
| `DesignacionController.php` | Twin escribe `FROM-TWIN.md` antes de tocar. Orquestador confirma. |
| `RevisionController.php` | Ídem |
| Cualquier vista que otro está editando | Revisar `LOCKS.md` primero |

---

## 4. FLUJO DE TRABAJO DEL TWIN

1. **Leer** `STATUS.md`, `TO-TWIN.md`, `LOCKS.md`
2. **Elegir** issue del plan (o seguir instrucción del Orquestador)
3. **Escribir** lock en `LOCKS.md`
4. **Leer** los archivos que va a modificar
5. **Hacer** los cambios
6. **Probar** — mínimo `php artisan test`
7. **Escribir** en `FROM-TWIN.md` el resultado
8. **Borrar** su lock de `LOCKS.md`
9. **Esperar** revisión del Orquestador

### Si algo sale mal

El Twin NUNCA hace commit sin revisión del Orquestador.
Si un test falla después de sus cambios:

```md
## Alerta: 2026-07-29 16:00
H-02: 82/83 tests. Falló DesignacionTest::test_create.
Revertí el cambio en DesignacionReportService.php:45.
El problema: el LEFT JOIN excluye carreras sin designaciones.
Propongo: cambiar a RIGHT JOIN o usar COALESCE.
¿Sigo con esta corrección?
```

El Orquestador responde en `TO-TWIN.md`.

---

## 5. ACTITUD

- **Nada es seguro hasta que pasa los tests.** No importa si "lógicamente debería funcionar".
- **Las sugerencias del Twin son bienvenidas.** Si ves una mejor arquitectura, decilo en `FROM-TWIN.md`.
- **El Orquestador puede estar equivocado.** Si una instrucción parece mala, cuestionala. Pero después de discutir, la decisión final es del Orquestador.
- **Preferimos código aburrido y correcto** antes que elegante y roto.
- **Si hay duda, preguntar.** Mejor preguntar 2 veces que romper 1 cosa.

---

## 6. COMANDOS RÁPIDOS DEL TWIN

```
/status       → Lee STATUS.md y responde resumen
/tomar H-XX   → Toma el issue, escribe lock, empieza a trabajar
/revisar      → Pide al Orquestador que revise su diff
/bloqueado    → Reporta bloqueo en FROM-TWIN.md + STATUS.md
/hecho        → Marca completado, borra lock, espera review
```

---

## 7. CHECKLIST PRE-COMMIT (OBLIGATORIO)

El Orquestador corre esto antes de integrar cualquier cambio del Twin:

```bash
php artisan test --compact          # 83/83
php artisan route:list --except-vendor  # Sin rutas rotas
```

# Plan de simplificación — Designación de Docentes (UATF)

> Este documento vive en el repo para poder retomarse en cualquier sesión futura de
> Claude Code. Si estás leyendo esto en una sesión nueva: **no asumas que un paso está
> hecho solo porque está en este archivo** — revisá la sección "Estado" al final y,
> si hace falta, confirmá con el dueño del proyecto en qué paso quedaron.

## Origen y objetivo

Aprobado el 2026-07-15, después de cerrar el roadmap de 6 semanas (Fases 1-6, ver
`docs/bitacora/`) y de probar el sistema con datos falsos a escala real (25 carreras,
~150 materias, ~200 grupos, ~570 designaciones — ver `database/seeders/`).

Con el sistema ya funcionando de punta a punta, el dueño del proyecto notó que **hay
demasiadas pantallas y rutas para una tarea que debería ser simple: asignar un docente a
un grupo**. El objetivo de esta segunda etapa no es agregar funcionalidad, es lo
contrario:

1. **Simplificar** — menos pantallas, menos rutas, menos formas distintas de llegar a lo
   mismo.
2. **Más intuitivo** — que un usuario nuevo entienda el flujo sin explicación.
3. **Más rápido** — que designar docentes a un grupo tome el menor número de clics/pasos
   posible, priorizando la asignación masiva por sobre el formulario individual.

Esto es un rediseño de UX/arquitectura sobre un sistema que ya funciona, no una
corrección de bugs. Por eso avanza en pasos chicos con aprobación del dueño en cada uno
(igual que el roadmap original — ver memoria `feedback-phase-checkpoints`), en vez de
hacerse todo de una vez.

## Alcance acordado

Confirmado con el dueño el 2026-07-15 (vía preguntas directas antes de armar este plan):

- ✅ **Pantallas de designaciones** — están en juego las 8 vistas actuales (ver tabla
  abajo). Varias se pisan entre sí.
- ✅ **Catálogos poco usados** — Gestiones y Periodos casi no se tocan (se crean ~1 vez al
  año) pero hoy ocupan el mismo nivel jerárquico en el sidebar que Carreras/Materias/
  Docentes, que sí se usan todo el tiempo.
- ✅ **Backend (rutas y controllers)** — no es solo maquillaje de UI. Si una pantalla
  desaparece, su ruta y su lógica de controller también deben desaparecer.
- ❌ Fuera de esta ronda: modelo de datos (`designaciones`, `designaciones_historial`,
  columnas `estado`/`aprobado_por`), autenticación/roles, y todo lo que ya está marcado
  como "no asumir sin confirmación" en `CLAUDE.md`.

**Cómo debe sentirse "más rápido" (decisión del dueño):** la asignación masiva por
carrera (`AsignarPorCarrera`) pasa a ser el **camino principal** para crear
designaciones. El formulario individual (`Create.jsx`) dejará de ser la vía principal.

**Qué pantalla de "ver designaciones" sobrevive (decisión del dueño):** de las tres vistas
que hoy muestran designaciones de formas distintas —`PorCarrera` (grid de carreras),
`Lista` (listado plano filtrable) y `Carrera` (detalle de una carrera con tabs)—, **la que
queda es `Carrera` (detalle)**. Las otras dos se eliminan.

## Inventario actual (para no perder de vista qué existe hoy)

| Ruta | Pantalla | Qué hace | Destino propuesto |
|---|---|---|---|
| `GET /designaciones` | `PorCarrera.jsx` | Grid de las 25 carreras con stats, dona y menú de 3 puntos | **Eliminar** — su función de "elegir carrera" la absorbe el catálogo de Carreras |
| `GET /designaciones/lista` | `Lista.jsx` | Listado plano de designaciones individuales con filtros | **Eliminar** — se pierde por elección del dueño, todo pasa por `Carrera` |
| `GET /designaciones/carrera/{carrera}` | `Carrera.jsx` | Detalle de una carrera: tabs de materias, estado de cada una | **Se queda y se convierte en la pantalla central** (ver Paso 1) |
| `GET/POST /designaciones/create` | `Create.jsx` | Formulario individual para una designación | **Eliminar** una vez que el roster cubra el caso de uso (Paso 1 define si queda algún caso borde) |
| `GET/POST /designaciones/{id}/edit` | `Edit.jsx` | Formulario individual para editar una designación existente | **Se integra** a la edición inline del roster |
| `GET /designaciones/asignar`, `POST .../asignar` | `AsignarPorCarrera.jsx` | Tabla para asignar varios grupos pendientes de una carrera a la vez | **Se integra** al roster (deja de ser pantalla aparte) |
| `GET /designaciones/copiar`, `POST .../copiar` | `Copiar.jsx` | Copiar designaciones de una gestión/periodo a otro, editando antes de guardar | **Se integra** al roster como una acción ("Copiar desde otra gestión") |
| `GET /designaciones/{id}/historial` | `Historial.jsx` | Historial de cambios de una designación | **Se queda tal cual**, enlazado desde el roster |

Controllers backend involucrados hoy: `DesignacionController` (resource completo +
`lista`, `carrera`, `historial`) y `DesignacionMasivaController` (`asignarForm`/
`asignarStore`, `copiarForm`/`copiarStore`). Se espera que terminen fusionados o muy
reducidos.

## Concepto propuesto: "roster" por carrera

En vez de tener pantallas separadas para ver, crear, editar y asignar en masa, la idea es
que `Carrera.jsx` se convierta en una sola tabla ("roster") por carrera + gestión +
periodo:

- Una fila por grupo (asignado o no), no por materia.
- Columna de docente **editable ahí mismo** (select/autocompletar), sin navegar a otra
  pantalla — cubre a la vez el caso "crear" y el caso "editar".
- Aviso de horas (>6h) y choque de horario en vivo por fila, igual que ahora.
- Guardar varias filas de una sola vez (reutiliza la idea de `AsignarPorCarrera`, pero
  aplicada a todos los grupos, no solo a los pendientes).
- El selector de gestión/periodo que ya existe en `Carrera.jsx` sigue ahí arriba.
- "Copiar desde otra gestión" se vuelve un botón dentro de esta misma pantalla, no una
  ruta aparte.

Esto es una **propuesta a validar en el Paso 1**, no una decisión cerrada — el dueño debe
confirmar este concepto (o pedir ajustes) antes de que se toque una sola línea de código.

## Los 7 pasos

Cada paso termina con una pausa obligatoria para feedback del dueño (probar a mano y dar
el visto bueno) antes de arrancar el siguiente — no se encadenan solos.

### Paso 1 — Validar el concepto (sin tocar código)
Presentar el concepto de "roster" con mockup o descripción concreta, confirmar qué pasa
con los casos borde (¿queda algún formulario individual para casos raros?, ¿qué pasa con
una designación `rechazada` — se reintenta desde la misma fila?), y decidir el destino
final de Gestiones/Periodos en el sidebar. Nada de código todavía.

### Paso 2 — Backend: endpoint de roster
Nueva acción de controller que devuelve todos los grupos de una carrera (con o sin
designación) para una gestión/periodo, más un endpoint de guardado masivo (upsert de
varias designaciones, reutilizando `CargaAcademicaService` para los avisos). Convive con
las rutas viejas por ahora — nada se borra todavía.

### Paso 3 — Frontend: tabla-roster editable en `Carrera.jsx`
Reemplazar las tabs actuales por la tabla-roster: edición inline de docente, avisos en
vivo, guardado en bloque.

### Paso 4 — Migrar accesos y borrar pantallas redundantes
Sidebar "Designación de Docentes" pasa a apuntar al flujo nuevo. Se borran
`PorCarrera.jsx`, `Lista.jsx`, `Create.jsx`, `Edit.jsx`, `AsignarPorCarrera.jsx` y sus
rutas/controllers asociados una vez confirmado que el roster cubre todos sus casos.

### Paso 5 — "Copiar entre gestiones" integrado
Meter la función de `Copiar.jsx` como una acción dentro del roster, no como pantalla
aparte.

### Paso 6 — Catálogos: Gestiones/Periodos fuera del menú principal
Moverlos a una sección secundaria del sidebar (según lo que se haya definido en el
Paso 1), para que el menú principal quede enfocado en lo que se usa todos los días.

### Paso 7 — Limpieza final y documentación
Barrido de código/rutas muertas, actualizar `design.md` / `ERD.md` / `CLAUDE.md` con la
arquitectura nueva, ampliar tests automatizados para el flujo roster, bitácora de cierre.

## Estado

- [ ] Paso 1 — Validar el concepto
- [ ] Paso 2 — Backend: endpoint de roster
- [ ] Paso 3 — Frontend: tabla-roster
- [ ] Paso 4 — Migrar accesos y borrar pantallas redundantes
- [ ] Paso 5 — Copiar entre gestiones integrado
- [ ] Paso 6 — Catálogos fuera del menú principal
- [ ] Paso 7 — Limpieza final y documentación

**Último avance:** plan recién aprobado (2026-07-15), todavía no arrancó el Paso 1.

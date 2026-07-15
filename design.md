# design.md — Sistema de diseño (UATF Designaciones)

Guía obligatoria para TODA pantalla nueva o modificada. Si una vista se aparta de esto,
es un bug de diseño. Referencias vivas: `resources/js/Pages/Designaciones/PorCarrera.jsx`
y `resources/js/Pages/Designaciones/Carrera.jsx` son la implementación canónica.

## Principios

1. **Diseño plano.** Colores sólidos siempre.
   **PROHIBIDO usar gradientes** (`bg-gradient-*`, `from-*`, `to-*`, `via-*`) en cualquier
   elemento: botones, iconos, barras, tarjetas, fondos. Esto incluye explícitamente el
   botón "+ Nueva designación" (tuvo gradiente una vez y se eliminó a pedido del dueño —
   no volver a introducirlo).
2. **Los detalles hacen premium**: micro-interacciones sutiles, tipografía cuidada,
   espaciado consistente. Nada llamativo ni decorativo porque sí.
3. **Datos reales solamente.** No inventar columnas/campos que el modelo no tiene
   (ej. Facultad, Modalidad, Créditos, Semestre no existen). Si un mockup los muestra,
   se reemplazan por datos reales o se omiten.
4. **Todo en español** (UI, labels, mensajes, código de vistas en la medida razonable).
5. Sin emojis en la interfaz. Iconos: SVG inline de trazo (`stroke`), 24×24,
   `strokeWidth 1.8`, `strokeLinecap/Linejoin round`.

## Tokens

### Colores

| Rol | Clase |
|---|---|
| Primario (botones sólidos, links activos, paginación activa) | `bg-blue-900`, hover `bg-blue-800`, texto links `text-blue-700` |
| Éxito / asignado / activa | `green-500` (punto/barra), tono `verde` (badge — ver sección Badges) |
| Advertencia / pendiente | `amber-400` (barra), tono `ambar` (badge), icono `text-amber-600` |
| Peligro / sin datos / rechazada | `red-500` (punto/barra), tono `rojo` (badge) |
| Informativo / por asignar | `blue-500` (punto), tono `azul` (badge) |
| Neutro / deshabilitado | tono `gris` (badge), `bg-gray-50 text-gray-500 ring-gray-400/30` en otros usos (tiles, tracks) |
| Fondo de página | `bg-gray-50` (lo da `AppLayout`) |
| Superficies (cards, tablas) | `bg-white` con `border border-gray-200/80` y `shadow-sm` |
| Texto principal / secundario / terciario | `text-gray-900` / `text-gray-500` / `text-gray-400` |

Colores para tiles de entidades (iconos de carrera/materia): paleta rotativa por `id % 6`:
`blue`, `emerald`, `violet`, `rose`, `amber`, `cyan` — siempre `bg-{color}-50 text-{color}-700 ring-{color}-200/60`.

### Tipografía

- Fuente: Instrument Sans (ya configurada).
- Títulos de página: `text-2xl font-bold tracking-tight text-gray-900`.
- Títulos de card: `font-semibold tracking-tight text-gray-900`.
- Números grandes (KPIs): `text-[26px] font-semibold leading-8 tracking-tight tabular-nums`.
- **Todo número tabular**: agregar `tabular-nums` a cifras, contadores, paginación.
- Micro-labels (encabezados de tabla, labels de filtros):
  `text-[11px] font-semibold uppercase tracking-wider text-gray-400`.

### Radios y sombras

- Cards y tablas: `rounded-xl`.
- Botones, inputs, selects, tiles de icono, ítems de menú: `rounded-lg`.
- Badges y puntos: `rounded-full`.
- Sombra base: `shadow-sm`. Menús flotantes: `shadow-lg shadow-gray-200/60`.
- Bordes interiores sutiles: `ring-1 ring-inset` (badges, tiles, tracks de barras).

## Componentes

### Botones

- **Primario**: `rounded-lg bg-blue-900 px-4 py-2 text-sm font-medium text-white shadow-sm
  transition-colors hover:bg-blue-800 active:scale-[0.98]`. Plano, sin gradiente, sin ring.
- **Secundario/outline**: `rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm
  font-medium text-gray-600 shadow-sm transition-colors hover:border-gray-300 hover:bg-gray-50`.
- **Deshabilitado**: `disabled:cursor-not-allowed disabled:opacity-40`.
- Botones de acción en fila de tabla: mismos estilos en tamaño `px-3.5 py-1.5 text-xs`.

### Badges de estado

Componente `Components/Badge.jsx`. Patrón: `inline-flex items-center gap-1 rounded-full
border bg-white px-2.5 py-1 text-xs font-medium` + ícono de 14×14 (`h-3.5 w-3.5`) a la
izquierda en vez de punto — fondo blanco, borde e ícono/texto del mismo tono (nunca relleno
sólido ni `ring`). Reemplaza el patrón anterior (`bg-{color}-50 ring-1 ring-inset` + punto)
en toda la web — no queden badges hechos a mano por pantalla.

Uso: `<Badge tono="verde" icono="check">Aprobada</Badge>`. Cada pantalla mapea su propio
estado de dominio (`aprobada`, `habilitado`, `asignada`, etc.) a un `tono` + `icono` con un
objeto local — el componente no conoce esos nombres, solo tonos.

| Tono | Clases | Ícono | Uso típico |
|---|---|---|---|
| `verde` | `border-green-200 text-green-700` | `check` | Éxito / activa / asignada / habilitada / aprobada |
| `ambar` | `border-amber-200 text-amber-700` | `reloj` | Advertencia / pendiente / propuesta |
| `rojo` | `border-red-200 text-red-600` | `equis` | Peligro / rechazada |
| `azul` | `border-blue-200 text-blue-700` | `reloj` | Informativo / por asignar |
| `gris` | `border-gray-300 text-gray-500` | `vacio` | Neutro / deshabilitada / sin asignar / sin grupos |

Nota: `propuesta` pasa de gris a **ámbar** — es "pendiente de decisión", que ya es un rol
de color definido arriba; antes usaba gris por inconsistencia entre pantallas, no por
decisión de diseño.

### Tablas

- Contenedor: `overflow-hidden rounded-xl border border-gray-200/80 bg-white shadow-sm`
  con `overflow-x-auto` interno.
- `thead`: `bg-gray-50/80`, celdas con micro-label (ver tipografía).
- Filas: `divide-y divide-gray-100`, hover `hover:bg-gray-50/60 transition-colors`,
  entrada con clase `fila-entra` y `animationDelay` escalonado (30ms por fila, tope 240ms).
- Celda de entidad: tile de color (`h-9 w-9 rounded-lg` + inicial de la sigla) + nombre
  (`font-medium text-gray-900`) + sigla debajo (`text-xs text-gray-400`).
- Pie de tabla: "Mostrando X a Y de Z ..." (`text-[13px] text-gray-400 tabular-nums`,
  cifras en `font-medium text-gray-600`) + paginación.

### Paginación

Botones `h-8 min-w-8 rounded-lg px-2 text-sm`: activo `bg-blue-900 text-white shadow-sm`;
normal outline gris; deshabilitado `text-gray-300`. Flechas `‹` `›`.

### Formularios y filtros

- Inputs: `rounded-lg border border-gray-200 py-2 text-sm shadow-sm transition-colors
  hover:border-gray-300 focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20`.
- Selects: **nunca el chevron nativo** — `appearance-none` + chevron SVG propio
  (componente `Select` de las páginas canónicas).
- Búsqueda: icono lupa a la izquierda (`pl-9`), botón × para limpiar cuando hay texto.
- Barra de filtros: card blanca con los controles; botón "Filtros" (icono embudo) que
  limpia todo, deshabilitado si no hay filtros activos.
- Labels de filtro: micro-label encima del control.

### Barras de progreso

Track: `h-1.5 rounded-full bg-gray-100 ring-1 ring-inset ring-gray-200/50`.
Relleno: color sólido según semántica, `transition-[width] duration-500 ease-out`.
Texto: cantidad en `font-semibold` del color + `(%)` en `text-xs text-gray-400`.

### Donas / anillos (SVG)

- `viewBox 0 0 100 100`, radio 40, `strokeWidth` 11–13, track `#f3f4f6`.
- Segmentos: verde `#22c55e`, ámbar `#f59e0b`, rojo `#ef4444`, azul `#3b82f6`, gris `#d1d5db`.
- Con 2+ segmentos: `strokeLinecap round` y separación ~1.8 unidades.
- Valor central absoluto (`total` o `%`) con `tabular-nums`.
- Transición: `[transition:stroke-dasharray_.5s_ease,stroke-dashoffset_.5s_ease]`.

### Menús flotantes (tres puntos, popovers)

Backdrop invisible `fixed inset-0 z-10` que cierra al clic + panel
`menu-pop absolute z-20 rounded-lg border border-gray-200/80 bg-white py-1 shadow-lg
shadow-gray-200/60`. Ítems con icono gris a la izquierda.

### Cards laterales (aside)

- Aside: `w-full xl:w-80 shrink-0 space-y-5`.
- "Acciones rápidas": ítems con tile de icono que se tiñe de azul en hover, chevron
  que se desliza (`group-hover:translate-x-0.5`). Solo enlazar a funciones que existen —
  nada de links muertos.
- Card de info: `dl` con filas `flex justify-between`, etiqueta gris / valor `font-medium`.

### Estados vacíos

Icono en círculo gris (`h-12 w-12 rounded-full bg-gray-50 ring-1 ring-inset`), título
`font-medium text-gray-700`, subtítulo gris, y acción de recuperación (ej. "Limpiar filtros").
Nunca una fila vacía sola.

### Tabs

`border-b border-gray-200` + botones `border-b-2 pb-3 text-sm font-medium`:
activo `border-blue-700 text-blue-700`; inactivo `border-transparent text-gray-500
hover:border-gray-200 hover:text-gray-700`.

## Animaciones permitidas (y ninguna otra)

| Uso | Mecanismo |
|---|---|
| Hover general | `transition-colors` / `transition-all` (150–200ms) |
| Tarjetas KPI | `hover:-translate-y-0.5 hover:shadow-md duration-200` |
| Menús flotantes | clase `menu-pop` (120ms, definida en `app.css`) |
| Entrada de filas | clase `fila-entra` + stagger 30ms (tope 240ms) |
| Barras de progreso | `transition-[width] duration-500 ease-out` |
| Donas | transición de `stroke-dasharray/offset` 500ms |
| Botón presionado | `active:scale-[0.98]` |

Prohibido: parallax, bounce, spinners decorativos, animaciones de más de 600ms.

## Patrones de datos (Inertia)

- **Listas no acotadas** (crecen con el uso): paginación y filtros **server-side**,
  `->paginate(N)->withQueryString()`, visitas con
  `{ preserveState: true, preserveScroll: true, replace: true, only: [props que cambian] }`.
- **Conjuntos acotados por entidad** (ej. materias de una carrera): enviar todo y
  tabs/búsqueda/paginación **client-side** (instantáneo, sin round-trips).
- Búsqueda server-side: debounce 350ms.
- Los catálogos estáticos (gestiones, periodos) se excluyen del `only` para no
  re-serializarlos en cada filtro.

## Layout

- Página = contenido principal (`min-w-0 flex-1`) + aside opcional (`xl:w-80`),
  `flex flex-col gap-6 xl:flex-row`.
- Pantallas de detalle: breadcrumb (`Sección › Detalle`) arriba a la izquierda +
  botón "← Volver" outline arriba a la derecha.
- El título de página vive dentro del contenido (AppLayout sin prop `title`);
  el chip de usuario/cerrar sesión queda arriba a la derecha (lo da AppLayout).
- Sidebar oscuro (`bg-gray-900`): agrupado por secciones (`navGroups` en `AppLayout.jsx`).
  Título de sección: `text-[11px] font-semibold uppercase tracking-wider text-gray-500`.
  No agregar secciones o ítems nuevos sin pedido explícito del dueño (la sección
  "Catálogos" con sus 6 entidades fue pedida en el roadmap de fase 2; el ítem
  "Dashboard", primero en la lista, fue pedido en el roadmap de fase 5).

## Prohibiciones (resumen)

1. **Gradientes — nunca, en nada.**
2. Chevron nativo de `<select>`.
3. Columnas/datos inventados que el modelo no tiene.
4. Links muertos o acciones que no llevan a nada funcional.
5. Emojis en la UI.
6. Texto de interfaz en inglés.
7. Sombras duras (`shadow-xl`+) o bordes oscuros (`border-gray-400`+) en superficies.
8. Ítems nuevos en el sidebar sin pedido explícito.

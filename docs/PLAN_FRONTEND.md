# Plan de Optimización Frontend — Designaciones UATF

> **Dividido en 2 trabajadores.** Cada tarea tiene instrucciones exactas. Seguir las
> convenciones al pie de la letra — al final se merge todo y se verifica.

---

## Convenciones compartidas (OBLIGATORIO para ambos trabajadores)

### Estilos de componentes

Todos los componentes nuevos usan estas clases exactas (no inventar variaciones):

```
Botón primario:   bg-blue-900 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-800 active:scale-[0.98] transition
Botón secundario: border border-gray-200 bg-white px-4 py-2 rounded-lg text-gray-700 hover:border-gray-300 hover:bg-gray-50 transition
Card:             rounded-xl border border-gray-200/80 bg-white shadow-sm
Input:            w-full rounded-lg border border-gray-200 px-3 py-2 text-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 outline-none transition
Select:           (usar componente Select.jsx existente)
Header de tabla:   text-[11px] font-semibold uppercase tracking-wider text-gray-400
Fila de tabla:    border-b border-gray-100 hover:bg-gray-50/50 transition
```

### Rutas de archivos

- Componentes nuevos → `resources/js/Components/NombreComponente.jsx`
- Hooks nuevos → `resources/js/Hooks/useNombre.js`
- Páginas existentes → `resources/js/Pages/Carpeta/Archivo.jsx`

### Convenciones de código React

- `export default function Componente() { ... }` (no `const Componente = () =>`)
- Imports de React no son necesarios (React 19)
- Usar `router` de `import { router, Link, useForm } from '@inertiajs/react'`
- Usar `route()` global de Ziggy (no importar)
- No usar `;` al final de statements (Pint lo quita)
- Indentación: 4 espacios

### Qué NO tocar (bajo ninguna circunstancia)

```
app/Http/                    — backend, ya optimizado
resources/js/Layouts/        — AppLayout funciona bien
resources/js/Components/ComboboxDocente.jsx
resources/js/Components/MenuFlotante.jsx
resources/js/Components/Icono.jsx
resources/js/Components/paletaIcono.js
resources/js/Pages/Designaciones/Carrera.jsx   — roster principal
resources/js/Pages/Designaciones/AsignarPorCarrera.jsx
resources/js/Pages/Designaciones/Create.jsx    — se modifica pero no se borra
resources/js/Pages/Designaciones/Edit.jsx      — se modifica pero no se borra
```

---

## Trabajador A — Hooks + FilterBar

Este trabajador hace el **Paso 1** y el **Paso 4**. Son dependientes: el Paso 4 usa el hook creado en el Paso 1.

### Tarea A1 — Crear `useDebouncedSearch.js`

**Archivo nuevo:** `resources/js/Hooks/useDebouncedSearch.js`

**Qué hace:** Custom hook que retorna `[valor, setValor]` con debounce de 350ms. Cuando el usuario escribe, no dispara la búsqueda inmediatamente — espera 350ms después del último keystroke.

**Patrón exacto a seguir** (copiar de `resources/js/Pages/Docentes/Index.jsx` líneas 12-27):

```javascript
// ANTES (duplicado en Docentes/Index.jsx y PorCarrera.jsx):
const [busqueda, setBusqueda] = useState(filtros.q || '')
const timeoutRef = useRef(null)

const handleBusqueda = (value) => {
    setBusqueda(value)
    clearTimeout(timeoutRef.current)
    timeoutRef.current = setTimeout(() => {
        router.get(route(route().current()), { ...filtros, q: value || undefined }, { preserveState: true })
    }, 350)
}
```

**Hook resultante:**

```javascript
import { router } from '@inertiajs/react'
import { useRef, useState } from 'react'

export function useDebouncedSearch(filtros, delay = 350) {
    const [valor, setValor] = useState(filtros.q || '')
    const timeoutRef = useRef(null)

    const buscar = (value) => {
        setValor(value)
        clearTimeout(timeoutRef.current)
        timeoutRef.current = setTimeout(() => {
            router.get(
                route(route().current()),
                { ...filtros, q: value || undefined },
                { preserveState: true, replace: true }
            )
        }, delay)
    }

    return [valor, buscar]
}
```

**Archivos a modificar después de crear el hook:**

1. `resources/js/Pages/Docentes/Index.jsx`
   - Eliminar: `useState`, `useRef`, la variable `busqueda`, la función `handleBusqueda`
   - Agregar: `import { useDebouncedSearch } from '@/Hooks/useDebouncedSearch'`
   - En el componente: `const [busqueda, handleBusqueda] = useDebouncedSearch(filtros)`
   - Eliminar el import de `useRef` si ya no se usa para nada más

2. `resources/js/Pages/Designaciones/PorCarrera.jsx`
   - Mismo patrón: eliminar el state + ref + handleBusqueda duplicado
   - Reemplazar por `useDebouncedSearch(filtros)`
   - Nota: PorCarrera usa `busqueda` como nombre de variable, asegurar que la interfaz sea la misma

### Tarea A2 — Crear `useResumenCarga.js`

**Archivo nuevo:** `resources/js/Hooks/useResumenCarga.js`

**Qué hace:** Custom hook que observa campos del formulario (Id_docente, Id_materia, Id_grupo, Id_gestion, Id_periodo) y hace `router.reload` parcial cada vez que cambian, para obtener el `resumenCarga` actualizado del backend.

**Patrón exacto a seguir** (copiar de `resources/js/Pages/Designaciones/Create.jsx` líneas 17-30):

```javascript
// ANTES (idéntico en Create.jsx y Edit.jsx):
useEffect(() => {
    const timeout = setTimeout(() => {
        router.reload({
            only: ['resumenCarga'],
            data: {
                Id_docente: data.Id_docente || '',
                Id_materia: data.Id_materia || '',
                Id_grupo: data.Id_grupo || '',
                Id_gestion: data.Id_gestion || '',
                Id_periodo: data.Id_periodo || '',
            },
            preserveState: true,
            preserveScroll: true,
        })
    }, 300)
    return () => clearTimeout(timeout)
}, [data.Id_docente, data.Id_materia, data.Id_grupo, data.Id_gestion, data.Id_periodo])
```

**Hook resultante:**

```javascript
import { router } from '@inertiajs/react'
import { useEffect } from 'react'

export function useResumenCarga(data) {
    useEffect(() => {
        const timeout = setTimeout(() => {
            router.reload({
                only: ['resumenCarga'],
                data: {
                    Id_docente: data.Id_docente || '',
                    Id_materia: data.Id_materia || '',
                    Id_grupo: data.Id_grupo || '',
                    Id_gestion: data.Id_gestion || '',
                    Id_periodo: data.Id_periodo || '',
                },
                preserveState: true,
                preserveScroll: true,
            })
        }, 300)
        return () => clearTimeout(timeout)
    }, [data.Id_docente, data.Id_materia, data.Id_grupo, data.Id_gestion, data.Id_periodo])
}
```

**Archivos a modificar después de crear el hook:**

1. `resources/js/Pages/Designaciones/Create.jsx`
   - Eliminar: el `useEffect` completo (líneas 17-30 aprox)
   - Agregar: `import { useResumenCarga } from '@/Hooks/useResumenCarga'`
   - En el componente: `useResumenCarga(data)` (antes del return)
   - Eliminar import de `useEffect` si ya no se usa

2. `resources/js/Pages/Designaciones/Edit.jsx`
   - Mismo patrón que Create.jsx

### Tarea A3 — Expandir `FilterBar` y migrar páginas

**Archivo a modificar:** `resources/js/Components/FilterBar.jsx`

**Qué tiene hoy** (34 líneas): Recibe un array `campos` de configs de selects, renderiza cada uno como `<Select>` + botón "Limpiar filtros".

**Qué agregarle:**
1. Soporte para un campo de búsqueda con texto (tipo `<input>`) que use `useDebouncedSearch`
2. Soporte para `layout: 'compact'` — una fila horizontal en vez de wrapping

**Interface actual de FilterBar:**

```jsx
<FilterBar
    campos={[
        { nombre: 'gestion_id', valor: filtros.gestion_id, opciones: gestiones, label: 'Gestión', placeholder: 'Todas' },
        { nombre: 'periodo_id', valor: filtros.periodo_id, opciones: periodos, label: 'Periodo', placeholder: 'Todos' },
    ]}
    filtros={filtros}
/>
```

**Interface expandida (agregar opcionalmente):**

```jsx
<FilterBar
    buscar={{ valor: busqueda, onChange: handleBusqueda }}  // NUEVO opcional
    campos={[...]}
    filtros={filtros}
/>
```

Cuando se pasa `buscar`, se renderiza un input de búsqueda a la izquierda de los selects.

**Archivos a modificar para usar FilterBar expandido:**

1. `resources/js/Pages/Docentes/Index.jsx`
   - Eliminar: el bloque de filtros inline (input de búsqueda + select de carrera + botón limpiar) — approximate líneas 33-52
   - Reemplazar por `<FilterBar buscar={...} campos={[...]} filtros={filtros} />`
   - Importar `FilterBar` si no lo tiene

2. `resources/js/Pages/Materias/Index.jsx`
   - Eliminar: filtros inline (select de carrera)
   - Reemplazar por `<FilterBar campos={[...]} filtros={filtros} />`

3. `resources/js/Pages/Grupos/Index.jsx`
   - Eliminar: filtros inline (select de materia + select de estado)
   - Reemplazar por `<FilterBar campos={[...]} filtros={filtros} />`

4. `resources/js/Pages/Designaciones/PorCarrera.jsx`
   - Este ya tiene filtros más complejos (búsqueda + gestion + periodo + estado)
   - Migrar a FilterBar con `buscar` + campos

**IMPORTANTE:** Al modificar estas páginas, mantener exactamente la misma funcionalidad:
- Los `router.get` con `preserveState: true` deben seguir funcionando igual
- Los nombres de parámetros en la URL no deben cambiar
- El botón "Limpiar filtros" debe seguir apareciendo cuando hay filtros activos

---

## Trabajador B — Componentes de UI reutilizables

Este trabajador hace los **Pasos 2, 3, 5, 6 y 7**. Son todos independientes entre sí.

### Tarea B1 — Crear `FormActions.jsx`

**Archivo nuevo:** `resources/js/Components/FormActions.jsx`

**Qué hace:** Renderiza los botones "Cancelar" y "Guardar" que aparecen al final de todo formulario Create/Edit.

**Patrón exacto a extraer** (repetido en 14 archivos — ver cualquiera de estos):

```jsx
// ANTES (bloque idéntico en 14 archivos):
<div className="flex justify-end gap-3 pt-4">
    <Link
        href={route('gestiones.index')}  // ← cambia la ruta según la página
        className="border border-gray-200 bg-white px-4 py-2 rounded-lg text-gray-700 hover:border-gray-300 hover:bg-gray-50 transition"
    >
        Cancelar
    </Link>
    <button
        type="submit"
        disabled={processing}
        className="bg-blue-900 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-800 active:scale-[0.98] transition disabled:opacity-50"
    >
        {processing ? 'Guardando...' : 'Guardar'}
    </button>
</div>
```

**Componente resultante:**

```jsx
import { Link } from '@inertiajs/react'

export default function FormActions({ onCancel, processing = false, label = 'Guardar' }) {
    return (
        <div className="flex justify-end gap-3 pt-4">
            <Link
                href={onCancel}
                className="border border-gray-200 bg-white px-4 py-2 rounded-lg text-gray-700 hover:border-gray-300 hover:bg-gray-50 transition"
            >
                Cancelar
            </Link>
            <button
                type="submit"
                disabled={processing}
                className="bg-blue-900 text-white px-4 py-2 rounded-lg font-medium hover:bg-blue-800 active:scale-[0.98] transition disabled:opacity-50"
            >
                {processing ? 'Guardando...' : label}
            </button>
        </div>
    )
}
```

**Archivos a modificar** (14 archivos Create/Edit — reemplazar el bloque de botones):

| Archivo | Ruta de Cancelar |
|---|---|
| `Carreras/Create.jsx` | `route('carreras.index')` |
| `Carreras/Edit.jsx` | `route('carreras.index')` |
| `Periodos/Create.jsx` | `route('periodos.index')` |
| `Periodos/Edit.jsx` | `route('periodos.index')` |
| `Gestiones/Create.jsx` | `route('gestiones.index')` |
| `Gestiones/Edit.jsx` | `route('gestiones.index')` |
| `Docentes/Create.jsx` | `route('docentes.index')` |
| `Docentes/Edit.jsx` | `route('docentes.index')` |
| `Materias/Create.jsx` | `route('materias.index')` |
| `Materias/Edit.jsx` | `route('materias.index')` |
| `Grupos/Create.jsx` | `route('grupos.index')` |
| `Grupos/Edit.jsx` | `route('grupos.index')` |
| `Designaciones/Create.jsx` | `route('designaciones.index')` |
| `Designaciones/Edit.jsx` | `route('designaciones.index')` |

En cada archivo:
1. Agregar import: `import FormActions from '@/Components/FormActions'`
2. Eliminar el bloque `<div className="flex justify-end gap-3 pt-4">...</div>` completo
3. Reemplazar por: `<FormActions onCancel={route('X.index')} processing={processing} />`
4. Eliminar import de `Link` si ya no se usa para nada más en el archivo

### Tarea B2 — Crear `DataTable.jsx`

**Archivo nuevo:** `resources/js/Components/DataTable.jsx`

**Qué hace:** Wrapper de tabla que encapsula el skeleton repetido en todas las páginas Index.

**Patrón exacto a extraer** (ver `resources/js/Periodos/Index.jsx` o cualquier Index):

```jsx
// ANTES (repetido en 7 archivos):
<div className="rounded-xl border border-gray-200/80 bg-white shadow-sm overflow-hidden">
    <div className="overflow-x-auto">
        <table className="w-full">
            <thead>
                <tr className="border-b border-gray-100 bg-gray-50/80">
                    {/* columnas — CAMBIA según la página */}
                </tr>
            </thead>
            <tbody>
                {/* filas — CAMBIA según la página */}
            </tbody>
        </table>
    </div>
</div>
```

**Componente resultante:**

```jsx
export default function DataTable({ children }) {
    return (
        <div className="rounded-xl border border-gray-200/80 bg-white shadow-sm overflow-hidden">
            <div className="overflow-x-auto">
                <table className="w-full">
                    {children}
                </table>
            </div>
        </div>
    )
}
```

**Uso:**

```jsx
<DataTable>
    <thead>
        <tr className="border-b border-gray-100 bg-gray-50/80">
            <th className="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400">Nombre</th>
            ...
        </tr>
    </thead>
    <tbody>
        {items.map(item => (
            <tr key={item.id} className="border-b border-gray-100 hover:bg-gray-50/50 transition">
                ...
            </tr>
        ))}
    </tbody>
</DataTable>
```

**Archivos a modificar** (7 archivos Index):

1. `Carreras/Index.jsx`
2. `Periodos/Index.jsx`
3. `Gestiones/Index.jsx`
4. `Docentes/Index.jsx`
5. `Materias/Index.jsx`
6. `Grupos/Index.jsx`
7. `Designaciones/Lista.jsx`

En cada archivo:
1. Agregar import: `import DataTable from '@/Components/DataTable'`
2. Eliminar: `<div className="rounded-xl border border-gray-200/80 bg-white shadow-sm overflow-hidden"><div className="overflow-x-auto"><table className="w-full">`
3. Eliminar los 3 `</` de cierre correspondientes al final
4. Reemplazar por `<DataTable>` y `</DataTable>`
5. El `thead` y `tbody` quedan DENTRO de DataTable, sin cambios

### Tarea B3 — Crear `FilaAcciones.jsx`

**Archivo nuevo:** `resources/js/Components/FilaAcciones.jsx`

**Qué hace:** Renderiza los botones de editar + eliminar que aparecen en la última columna de cada tabla Index.

**Patrón exacto a extraer** (ver `resources/js/Periodos/Index.jsx`):

```jsx
// ANTES (repetido en 7 archivos):
<td className="px-4 py-3 text-right">
    <div className="flex items-center justify-end gap-1">
        <Link
            href={route('periodos.edit', item.id)}
            className="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"
        >
            <Icono nombre="lapiz" className="h-4 w-4" />
        </Link>
        <ConfirmDeleteButton
            route={route('periodos.destroy', item.id)}
        />
    </div>
</td>
```

**Componente resultante:**

```jsx
import { Link } from '@inertiajs/react'
import Icono from './Icono'
import ConfirmDeleteButton from './ConfirmDeleteButton'

export default function FilaAcciones({ editRoute, deleteRoute }) {
    return (
        <td className="px-4 py-3 text-right">
            <div className="flex items-center justify-end gap-1">
                <Link
                    href={editRoute}
                    className="p-1.5 text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition"
                >
                    <Icono nombre="lapiz" className="h-4 w-4" />
                </Link>
                <ConfirmDeleteButton route={deleteRoute} />
            </div>
        </td>
    )
}
```

**Archivos a modificar** (7 archivos Index):

1. `Carreras/Index.jsx`
2. `Periodos/Index.jsx`
3. `Gestiones/Index.jsx`
4. `Docentes/Index.jsx`
5. `Materias/Index.jsx`
6. `Grupos/Index.jsx`
7. `Designaciones/Lista.jsx`

En cada archivo:
1. Agregar import: `import FilaAcciones from '@/Components/FilaAcciones'`
2. Eliminar el bloque `<td className="px-4 py-3 text-right">...</td>` completo
3. Reemplazar por: `<FilaAcciones editRoute={route('X.edit', item.id)} deleteRoute={route('X.destroy', item.id)} />`
4. Eliminar imports de `Icono` y `ConfirmDeleteButton` si ya no se usan en otro lugar del archivo

### Tarea B4 — Crear `GraficoAnillo.jsx` + `BarraProgreso.jsx`

**Archivo nuevo 1:** `resources/js/Components/GraficoAnillo.jsx`

**Qué hace:** Unifica los dos gráficos SVG duplicados:
- `Dona` de `PorCarrera.jsx` (donut completo, muestra conteo)
- `AnilloProgreso` de `Carrera.jsx` (anillo con porcentaje)

**Leer ambos sub-componentes** para entender las diferencias:
- `PorCarrera.jsx` líneas ~43-92 (`Dona`)
- `Carrera.jsx` líneas ~19-68 (`AnilloProgreso`)

**Componente resultante (aproximado — verificar contra los originales):**

```jsx
export default function GraficoAnillo({ variante = 'donut', datos, size = 120, strokeWidth = 12 }) {
    // variante: 'donut' = completo con colores, 'ring' = un solo color con porcentaje
    // datos: array de { valor, color } para donut, o { total, asignados } para ring
    // ...
}
```

**Archivos a modificar:**
- `PorCarrera.jsx`: eliminar sub-componente `Dona`, importar `GraficoAnillo`
- `Carrera.jsx`: eliminar sub-componente `AnilloProgreso`, importar `GraficoAnillo`
- **NOTA:** `Carrera.jsx` está en la lista de "NO tocar" — si hay duda, solo mover `Dona` de `PorCarrera.jsx` y dejar `AnilloProgreso` como está.

**Archivo nuevo 2:** `resources/js/Components/BarraProgreso.jsx`

**Qué hace:** Barra de progreso horizontal (ya existe como sub-componente inline en `PorCarrera.jsx`).

**Leer** `PorCarrera.jsx` líneas ~38-42 para el patrón.

**Archivos a modificar:**
- `PorCarrera.jsx`: eliminar sub-componente `BarraProgreso`, importar desde `Components/BarraProgreso.jsx`

### Tarea B5 — Unificar estilos de Login y Copiar

**Archivos a modificar:**

1. `resources/js/Pages/Auth/Login.jsx`
   - Tiene `inputClass` local definido en línea ~4 con estilo `rounded-md border-gray-300`
   - Eliminar esa constante
   - Importar `import { inputClass } from '@/Components/Field'`
   - Reemplazar todas las referencias a `inputClass` local por la importada
   - **Nota:** el estilo será `rounded-lg border-gray-200` en vez de `rounded-md border-gray-300` — esto es INTENCIONAL, es el diseño correcto del sistema

2. `resources/js/Pages/Designaciones/Copiar.jsx`
   - Tiene `selectClass` local definido en línea ~5 con estilo `rounded-md border-gray-300`
   - Eliminar esa constante
   - Importar `import Select from '@/Components/Select'`
   - Reemplazar los `<select className={selectClass} ...>` por `<Select ...>` del componente compartido
   - **Nota:** Verificar que `Select.jsx` acepta las mismas props que el `<select>` nativo

---

## Dependencias y orden

```
A1 (useDebouncedSearch)  ──┐
                            ├── A3 (FilterBar usa useDebouncedSearch)
A2 (useResumenCarga)     ──┘   (A2 no tiene dependientes)

B1 (FormActions)         ── independiente
B2 (DataTable)           ── independiente
B3 (FilaAcciones)        ── independiente
B4 (Gráficos)            ── independiente
B5 (Estilos)             ── independiente
```

**Paralelo posible:**
- Trabajador A hace A1 → A2 → A3 (en ese orden)
- Trabajador B hace B1 → B2 → B3 → B4 → B5 (en ese orden)
- Ambos trabajadores pueden correr AL MISMO TIEMPO

---

## Verificación final (después de que ambos terminen)

1. `npm run build` — debe compilar sin errores
2. Verificar que no hay imports muertos (archivos que importan algo que ya no existe)
3. Verificar que cada archivo modificado sigue siendo funcional (la interfaz no cambió)
4. Correr `php artisan test` — los tests de frontend no existen pero el backend no debe haber cambiado
5. Actualizar `docs/bitacora/` con el avance del día

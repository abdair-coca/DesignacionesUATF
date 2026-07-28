# Design System & UI Standard — Sistema de Designaciones UATF

Este documento define las reglas de diseño de interfaz (UI), componentes, paleta de colores y estilos tipográficos basados en la plantilla **Color Admin (UI Elements in Table)**.

---

## 🎨 Paleta de Colores Principal

| Color | Hex / Clase Tailwind | Uso Principal |
| :--- | :--- | :--- |
| **Dark Header** | `#2d353c` (`bg-[#2d353c]`) | Encabezados de paneles, tablas, modales y barra lateral. |
| **Primary Accent (Teal)** | `#00acac` (`bg-[#00acac]`) | Badges principales, enlaces activos, acciones de éxito. |
| **Primary Blue** | `#348fe2` (`bg-[#348fe2]`) | Botones principales de acción (`Editar`, `Guardar`, `Crear`). |
| **Secondary Row** | `#f2f4f8` (`bg-[#f2f4f8]`) | Filas alternadas en tablas para legibilidad óptima. |
| **Warning / Amber** | `#f59c1a` (`bg-[#f59c1a]`) | Estado Borrador / Propuesta y alertas de moderación. |
| **Danger / Red** | `#ff5b57` (`bg-[#ff5b57]`) | Observaciones, rechazos y acciones destructivas. |

---

## 📐 Reglas de Componentes UI

### 1. Cabecera de Panel / Tabla (`UI Elements in Table`)
- Fondo oscuro `#2d353c` con texto blanco de peso `font-bold text-xs`.
- Badge de módulo turquesa `#00acac` con bordes suaves rectangulares (`rounded-xs`).
- Botones de control de ventana a la derecha:
  - ⤢ Pantalla Completa (Gris)
  - ↻ Recargar Turquesa (`#00acac`)
  - &minus; Minimizar Ámbar (`#f59c1a`)
  - &times; Cerrar Rojo (`#ff5b57`)

### 2. Tablas de Datos
- **Bordes y Filas Alternadas**:
  - Filas pares: `bg-[#f2f4f8]`
  - Filas impares: `bg-white`
  - Efecto Hover: `hover:bg-gray-100/70`
- **Bordes Internos**: Separadores verticales de celda sutiles (`border-r border-gray-200/60`).

### 3. Distintivos de Estado (Badges)
- **Forma**: Rectangulares con esquinas levemente suavizadas (`rounded-xs`). **Queda prohibido el uso de ovoides totalmente redondeados (`rounded-full`)**.
- **Sin Emojis**: Ningún badge o encabezado debe incluir emojis. Debe ser 100% tipográfico y profesional.
- **Paleta Pastel Suave**:
  - **Borrador / Propuesta**: `bg-amber-100 text-amber-800 text-[11px] font-semibold px-2.5 py-1 rounded-xs`
  - **Enviado a Vicerrectorado**: `bg-cyan-100 text-cyan-800 text-[11px] font-semibold px-2.5 py-1 rounded-xs`
  - **Con Observaciones**: `bg-rose-100 text-rose-800 text-[11px] font-semibold px-2.5 py-1 rounded-xs`
  - **Oficial / Aprobado**: `bg-emerald-100 text-emerald-800 text-[11px] font-semibold px-2.5 py-1 rounded-xs`

### 4. Botones de Acción
- Esquinas rectangulares suavizadas (`rounded-xs`).
- **Botón Azul Sólido**: `bg-[#348fe2] hover:bg-[#2a72b5] text-white font-bold px-3 py-1.5 rounded-xs text-xs`
- **Botón Blanco con Borde**: `bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 font-bold px-3 py-1.5 rounded-xs text-xs`

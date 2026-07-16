# Plan de Rediseño del Dashboard (5 Pasos con Feedback)

Este plan detalla el rediseño estético y funcional del panel de control (Dashboard) principal de la aplicación, estructurado en **5 pasos incrementales**. Cada paso concluirá con un commit independiente y requerirá tu feedback para continuar, modificar o avanzar al siguiente.

Para este rediseño **se priorizará la fidelidad visual con el mockup suministrado** (permitiendo el uso de fondos oscuros, sparklines y los elementos visuales de la propuesta) en lugar de ceñirse estrictamente a las reglas de diseño plano tradicionales de `design.md`.

---

## Paso 1: Backend de Datos y Controlador
*   **Objetivo:** Modificar el backend para proveer datos reales de evolución histórica y progreso de carreras al Dashboard.
*   **Tareas:**
    *   Implementar en `DesignacionReportService.php` el método `evolucionDesignaciones(int $gestionId, int $periodoId): array` que agrupe las designaciones creadas en el periodo por mes/semana para graficar la serie de tiempo.
    *   Asegurar que `resumenPorCarrera` se llame en el controlador para proveer el progreso de cada carrera.
    *   Pasar `carrerasReport` y `evolucion` desde `DashboardController.php` a la vista.
*   **Entregable:** Pruebas aprobadas, compilación limpia y commit: `feat(dashboard): paso 1 - backend de reportes y serie de tiempo`.
*   **Feedback Requerido:** Confirmar la disponibilidad y formato de las variables en el cliente.

---

## Paso 2: Tarjeta "Progreso General" y Barra de Filtros
*   **Objetivo:** Construir la sección superior izquierda oscura de progreso general y la nueva barra de filtros.
*   **Tareas:**
    *   Crear la tarjeta "Progreso General" con fondo azul/slate oscuro (`bg-slate-900` o similar) con un anillo de progreso centralizado grande indicando el porcentaje de cobertura y los contadores en horizontal.
    *   Rediseñar la barra de filtros con selectores de Gestión y Periodo, botón de Filtros alineado a la izquierda, e indicador de actualización "Actualizado hace X min" con icono de recarga a la derecha.
*   **Entregable:** Compilación de la interfaz superior y commit: `feat(dashboard): paso 2 - progreso general y barra de filtros`.
*   **Feedback Requerido:** Revisión de colores, espaciados y dimensiones de la tarjeta de progreso general.

---

## Paso 3: Tarjetas de Estadísticas (StatTiles) con Sparklines
*   **Objetivo:** Rediseñar las 5 tarjetas de KPIs incluyendo minigráficos de tendencia (Sparklines).
*   **Tareas:**
    *   Actualizar las tarjetas de estadísticas para incorporar un icono redondeado semántico en la parte superior izquierda (ej. capas rojas, reloj ámbar, etc.).
    *   Añadir un elemento SVG Sparkline dinámico en la parte inferior de cada tarjeta con una línea de tendencia en el color semántico de la tarjeta (rojo, ámbar, verde) para denotar evolución.
*   **Entregable:** Renderizado de tarjetas de estadísticas con sparklines y commit: `feat(dashboard): paso 3 - tarjetas de estadisticas con sparklines`.
*   **Feedback Requerido:** Estética y animación de las líneas de tendencia (sparklines).

---

## Paso 4: Listado de Carreras, Gráfico de Evolución y Prioridad de Atención
*   **Objetivo:** Implementar los tres bloques principales del cuerpo de la página en la columna izquierda.
*   **Tareas:**
    *   **Designaciones por carrera:** Listado con barras de progreso horizontales en verde/amarillo/rojo según su porcentaje de cobertura de grupos, con su regla graduada en la base.
    *   **Evolución del periodo:** Gráfico de línea interactivo en SVG puro con puntos/nodos y sombreado inferior, acompañado de las cifras clave ("Actuales", "Pendientes", "Meta estimada").
    *   **Prioridad de atención:** Cuatro tarjetas de estado (Alta, Media, Baja, Info) con contadores rápidos de prioridades de cobertura.
*   **Entregable:** Cuerpo del panel izquierdo implementado y commit: `feat(dashboard): paso 4 - panel de carreras, evolucion y prioridad`.
*   **Feedback Requerido:** Interactividad del gráfico de evolución y legibilidad de las barras por carrera.

---

## Paso 5: Barra Lateral (Aside) con Resumen, Insights y Acciones Rápidas
*   **Objetivo:** Construir la columna lateral derecha del Dashboard tal como figura en el mockup.
*   **Tareas:**
    *   **Resumen de designaciones:** Tarjeta con gráfico de anillo (dona de color grande) indicando cantidad total centralizada y leyenda de porcentajes detallados al pie.
    *   **Insights:** Lista de 4 anotaciones inteligentes (completadas, sin docente, rechazadas, bajo límite de horas) con sus respectivos íconos semánticos.
    *   **Acciones rápidas:** Botones premium con iconos sobre cuadros de color y chevron derecho para navegación rápida.
*   **Entregable:** Dashboard completo terminado y commit: `feat(dashboard): paso 5 - barra lateral con resumen, insights y acciones`.
*   **Feedback Requerido:** Aprobación final de todo el panel de control.

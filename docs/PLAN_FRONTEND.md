# Plan de Migración Frontend Blade — Color Admin v2 — Designaciones UATF

> **Estrategia General**: Migración completa de Inertia.js/React a Laravel Blade monolítico con diseño inspirado en la plantilla **Color Admin v2**. La implementación se realizará sección por sección (página por página), solicitando referencias visuales antes de modificar cada vista y documentando componentes aprobados en `design.md`.

---

## 1. Directrices de Arquitectura y Roles

### Roles de Usuario
1. **Director de Carrera (Usuario Principal)**:
   - Administra el flujo de designaciones de su carrera.
   - Flujo principal: Selección del Docente → Asignación de Materias y Grupos con cálculo de carga horaria en tiempo real.
   - Crea, edita y envía propuestas de designación a revisión.
2. **Administrador / Vicedecano (Autoridad Superior)**:
   - Supervisa todas las carreras.
   - Revisa las propuestas enviadas.
   - Aprueba o rechaza las designaciones con registro de motivo de rechazo.

### Flujo Principal de Designación (Docente-Primero)
- En lugar del modelo tradicional por materias, la vista principal de designaciones mostrará la lista de **Docentes**.
- Al seleccionar o abrir un docente:
  - Se despliega el panel de asignación de materias/grupos disponibles en la gestión/periodo actual.
  - Se muestra la **barra de carga horaria acumulada** (horas actuales vs. límite máximo/mínimo) que se actualiza dinámicamente al marcar/desmarcar materias.
  - Botón de guardado y confirmación de la propuesta de designación.

---

## 2. Metodología de Trabajo Iterativa (Paso a Paso)

Para cada página/sección del plan:
1. **Definición de Sección**: Se anuncia la página/sección a construir.
2. **Requerimiento de Recursos Visuales**: Se solicita al usuario la imagen/screenshot de referencia (estilo **Color Admin v2**).
3. **Desarrollo y Réplica**: Se construyen la vista Blade y componentes necesarios replicando paleta de colores, tipografía, cabeceras, tablas y tarjetas.
4. **Validación y Actualización de `design.md`**: Tras la aprobación del usuario, se registran los nuevos componentes y tokens de diseño en `design.md`.

---

## 3. Plan Fase por Fase (Página por Página)

### **Fase 1: Layout Base & Sistema de Diseño (Color Admin v2)**
- **1.1 Componente Base & Layout Master (`resources/views/layouts/app.blade.php`)**:
  - Header superior (Logo UATF, título del sistema, notificaciones, perfil de usuario con rol).
  - Sidebar izquierdo estilo **Color Admin v2** (agrupado por secciones: Dashboard, Designación de Docentes, Catálogos, Revisiones/Aprobaciones).
  - Footer y contenedor principal responsive.
  - Inclusión de Tailwind CSS / CSS compilado, JavaScript interactivo (Alpine.js o JS vanilla) para micro-interacciones (desplegables, modales, alertas, cálculo dinámico).

- **1.2 Autenticación & Login (`resources/views/auth/login.blade.php`)**:
  - Pantalla de inicio de sesión estilizada con marca UATF y paleta Color Admin v2.

---

### **Fase 2: Dashboard Principal**
- **2.1 Dashboard General (`resources/views/dashboard/index.blade.php`)**:
  - Tarjetas de resumen estadístico (Total designaciones, activas, pendientes, carreras).
  - Gráficos de avance y distribución.
  - Accesos rápidos según rol (Director de Carrera vs Admin).

---

### **Fase 3: Módulo Principal — Designación de Docentes (Flujo Docente-Primero)**
- **3.1 Vista Principal por Carrera / Roster de Docentes (`resources/views/designaciones/carrera.blade.php`)**:
  - Filtros por Gestión y Periodo académico.
  - Tabla/Tarjetas de **Docentes** de la carrera con su estado actual de horas (Carga horaria asignada vs. disponible).
  - Panel interactivo / Modal de Asignación Masiva:
    - Selección de docente.
    - Checkbox de materias/grupos habilitados para asignar.
    - Contador dinámico de carga horaria (alerta si está por debajo del mínimo de 6h o sobrepasa el límite).
    - Opción de "Copiar todo" de una gestión/periodo a otro.
    - Acción de "Enviar a revisión".

- **3.2 Lista de Designaciones Registradas (`resources/views/designaciones/lista.blade.php`)**:
  - Listado tabular de designaciones creadas con filtros por estado (*Borrador*, *Enviada*, *Aprobada*, *Rechazada*).

- **3.3 Módulo de Revisión y Aprobación para Autoridad Superior (`resources/views/revisiones/index.blade.php`)**:
  - Vista exclusiva para Administrador/Vicedecano.
  - Permite revisar propuestas enviadas por los Directores de Carrera.
  - Acciones rápidas: *Aprobar*, *Rechazar con motivo*.

---

### **Fase 4: Catálogos del Sistema**
- **4.1 Gestión de Docentes (`resources/views/docentes/index.blade.php`, `create.blade.php`, `edit.blade.php`)**:
  - Listado de docentes con filtros por carrera/categoría y carga horaria.
- **4.2 Gestión de Materias (`resources/views/materias/index.blade.php`, `create.blade.php`, `edit.blade.php`)**:
  - Listado de materias por carrera con sus horas teóricas/prácticas.
- **4.3 Gestión de Grupos (`resources/views/grupos/index.blade.php`, `create.blade.php`, `edit.blade.php`)**:
  - Administración de grupos por materia.
- **4.4 Gestión de Carreras, Gestiones y Periodos**:
  - Vistas de catálogo institucional.

---

## 4. Orden de Ejecución Inmediata

1. **Paso Inicial**: Limpieza de `docs/PLAN_FRONTEND.md` e instalación del nuevo plan (Completado).
2. **Paso 1**: Construir el **Layout Base (`resources/views/layouts/app.blade.php`)** estilo Color Admin v2.
3. **Paso 2**: Construir la **Vista Principal de Designaciones (`resources/views/designaciones/carrera.blade.php`)** con el nuevo flujo docente-primero.
4. **Paso 3**: Construir la **Vista de Revisión / Aprobaciones (`resources/views/revisiones/index.blade.php`)** para el rol Admin.
5. **Paso 4**: Construir vistas de **Catálogos** y **Dashboard**.

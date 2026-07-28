---
name: redisenos-designaciones-uatf
description: >
  Protocolo y flujo estandarizado para la reestructuración, interfaz y gestión 
  de designaciones docentes en la Universidad Autónoma Tomás Frías (UATF).
---

# Protocolo y Flujo de Designación Docente UATF

Este skill define los lineamientos arquitectónicos, reglas de negocio y patrones de diseño de interfaz (Color Admin Plano) para la gestión de designaciones docentes.

## 1. Reglas de Negocio & Scoping

### Pantalla de Entrada al Sistema
- Toda sesión autenticada que acceda a `/` o navegue por la aplicación debe dirigirse prioritariamente a la **Lista de Designaciones por Carrera** (`/designaciones/lista`).

### Carga Horaria Multicarrera y Priorización
- **Tope Máximo**: Máximo 32 horas lectivas semanales por docente considerando la suma de materias dictadas en **todas las carreras** en la misma gestión y periodo.
- **Prioridad de Visualización en Carrera**:
  1. Docentes Titulares de la Carrera origen (`prioridad 1`).
  2. Docentes que hayan dictado al menos una materia históricamente en la carrera (`prioridad 2`).
  3. Resto de docentes (`prioridad 3`).

### Estados de una Designación (`estado`)
- **`propuesta`**: Estado automático asignado cada vez que una designación es creada, actualizada o reasignada por el Director de Carrera.
- **`aprobada`**: Asignado cuando el Vicerrectorado homologa y aprueba la solicitud.
- **`rechazada`**: Asignado cuando el Vicerrectorado observa o rechaza con motivo especificado.

### Autoridad de Revisión
- Las propuestas se envían exclusivamente al **Vicerrectorado**.
- Solo se permite enviar a revisión propuestas correspondientes a la **Gestión del Año Actual** (`date('Y')`). Las gestiones históricas anteriores quedan bloqueadas para envío.

---

## 2. Flujo de Pantallas e Interfaz (Color Admin Style)

### A. Lista de Designaciones por Carrera (`designaciones/lista.blade.php`)
- Presenta el selector global de Gestión y Periodo.
- Muestra el listado de carreras en cuadrícula/tarjetas planas, destacando la carrera del usuario autenticado con la etiqueta **"Mi Carrera"**.
- Enlace directo a "Gestionar Asignaciones".

### B. Asignación por Carrera (`designaciones/carrera.blade.php`)
- Tabla de asignación rápida por docente con `docentesProcesados` (Alpine.js).
- Desglose visible de horas locales + horas en me otras carreras.
- Modal de **"Copiar de Gestión Anterior"** con tabla de previsualización en tiempo real (`Nueva asignación`, `Reemplaza a...`, `Sin cambios`).
- Modal de confirmación para **"Enviar Propuesta a Vicerrectorado"**.

### C. Bandeja del Vicerrectorado (`revisiones/pendientes.blade.php`)
- Layout plano de 2 columnas estilo Color Admin Email Inbox.
- Lateral izquierdo con `FOLDERS` (`Inbox`, `Pendientes`, `Revisadas`, `Todas`) y `LABEL` con viñetas por carrera.
- Lista plana con avatares circulares de color y datos de solicitud.

### D. Detalle de Revisión Minimalista (`revisiones/revisar.blade.php`)
- Foco minimalista 100% en las materias, grupos y docentes designados.
- Evaluación interactiva local en memoria (`estado_local`).
- Finalización de revisión en lote mediante el botón destacado **"Terminar Revisión"** (`POST /revisiones/{revision}/completar`).

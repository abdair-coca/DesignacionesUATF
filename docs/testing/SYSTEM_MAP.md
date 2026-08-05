# Mapa funcional del sistema

## Alcance y arquitectura

Sistema Laravel web, PostgreSQL, autenticación por sesión, autorización por middleware y Policies, renderizado Blade y comportamiento puntual con Alpine.js. Flujo principal:

`routes/web.php` / `routes/auth.php` → `auth` y `rol:*` → controlador + validación → Policy → servicio transaccional → modelos/migraciones → vista Blade o JSON → eventos/notificaciones en base de datos.

La cartografía se basa en `routes/`, `app/Http/Controllers/`, `app/Services/`, `app/Models/`, `app/Policies/`, `app/Http/Middleware/`, `database/migrations/`, `resources/views/`, `config/` y `tests/Feature/`.

## Módulos

| Módulo | Responsabilidad | Entradas | Salidas | Tablas principales | Dependencias | Roles | Riesgos |
| --- | --- | --- | --- | --- | --- | --- | --- |
| Autenticación | Login, logout, rate limit, sesión y redirección por rol | Email, contraseña, sesión | Sesión autenticada, redirect, errores de validación | `users`, `sessions` | `AuthenticatedSessionController`, `RateLimiter`, `User` | Invitado, cualquier usuario válido | Credenciales, enumeración, redirección incorrecta |
| Autorización | Rechazar acceso por rol y por carrera/propuesta | Usuario, ruta, entidad | HTTP 403 o acceso autorizado | `users`, `carreras`, `propuestas` | `EnsureRole`, `CarreraPolicy`, `PropuestaPolicy`, `PropuestaVersionPolicy` | Director, Vicerrectorado | Aislamiento entre carreras; alcance global del Vicerrectorado |
| Catálogos académicos | Carreras, materias, malla, grupos, docentes, gestiones y periodos | IDs y relaciones académicas | Catálogos para formularios, roster y validaciones | `carreras`, `materias`, `malla_curricular`, `grupos`, `docentes`, `gestiones`, `periodos` | Modelos Eloquent, migraciones de normalización | Ambos indirectamente; Director opera su carrera | Relaciones heredadas/normalizadas inconsistentes |
| Propuestas del Director | Crear borrador, editar asignaciones, enviar, retirar y listar versiones | Gestión, periodo, descripción, filas de asignación | Vistas Blade, JSON, propuesta/versiones/snapshots | `propuestas`, `propuesta_designaciones`, `propuesta_versiones`, `propuesta_version_designaciones`, `propuesta_eventos` | `PropuestaController`, `PropuestaService`, Policies | Director de carrera | Envíos incompletos, concurrencia, estados distribuidos |
| Importación | Previsualizar/aplicar datos históricos u oficiales en borrador | Propuesta destino, gestión/periodo fuente | Impactos `new/same/replaces`, filas persistidas | `designaciones`, `propuestas`, `propuesta_designaciones`, `grupos`, `malla_curricular` | `ImportacionPropuestaService` | Director | Precedencia entre legado y propuestas oficiales; datos obsoletos |
| Revisión Vicerrectorado | Bandeja, revisión por fila o aprobación total, observaciones | Versión, decisiones, observación general | Estado de versión/propuesta, decisiones, vista y notificación | `propuesta_versiones`, `propuesta_version_designaciones`, `propuesta_version_decisiones`, `propuestas`, `propuesta_designaciones`, `propuesta_eventos` | `RevisionPropuestaController`, `RevisionPropuestaService`, Policy | Vicerrectorado | Decisiones incompletas, transición mixta, alcance global |
| Versionado y auditoría | Congelar envío, guardar snapshot inmutable y registrar eventos | Propuesta, filas, actor, tipo de evento | Snapshot, decisiones, historial de eventos | `propuesta_versiones`, `propuesta_version_designaciones`, `propuesta_version_decisiones`, `propuesta_eventos` | Constraints y triggers PostgreSQL | Director, Vicerrectorado | Dependencia de triggers PostgreSQL; auditoría parcial de edición |
| Notificaciones | Persistir avisos, listar, marcar una o todas como leídas | Evento, usuario notificado, URL | Filas Blade/paginadas, redirect, `read_at` | `notifications` | `Notifiable`, `NotificacionController`, servicios de propuesta/revisión | Director, Vicerrectorado | Normalización heurística de URLs; entrega solo dentro de aplicación |
| Legado de designaciones | Conservar asignaciones antiguas y proveer fuente de importación | Gestión, periodo, carrera/malla, estado | Filas históricas y candidatas a importación | `designaciones`, `designaciones_historial` | `Designacion`, scopes `active/forGestionPeriodo`, importador | Director indirectamente | Dos modelos de verdad; estados legado distintos |
| Presentación | Renderizar pantallas y estados derivados | View models de controladores | HTML, formularios, acciones, JSON puntual | No persiste directamente | Blade, Alpine CDN, Tailwind CDN | Ambos | Dependencia CDN; ausencia de pipeline frontend |
| Archivos públicos | Exponer disco local mediante ruta/framework | `GET/PUT storage/{path}` | Lectura/escritura del disco configurado | Disco `storage/app/public` o override de testing | `config/filesystems.php`, proveedor Laravel | Sin rol en rutas implícitas | `public/storage` no enlazado; superficie sin autenticación |

## Relaciones funcionales

- `Carrera` tiene malla curricular; `MallaCurricular` vincula `Carrera` y `Materia`; `Grupo` pertenece a una malla.
- `Propuesta` pertenece a carrera, gestión, periodo y creador; tiene filas actuales, versiones y eventos.
- `PropuestaVersion` pertenece a propuesta; sus filas snapshot son inmutables y cada snapshot admite una decisión.
- `User` puede ser `director_carrera` con `carrera_id`, o `vicerrectorado` sin carrera. La base impone esa combinación.
- `PropuestaService::enviar` copia valores desnormalizados al snapshot y notifica a usuarios Vicerrectorado.
- `RevisionPropuestaService::decidir` decide snapshots, actualiza filas actuales/propuesta y notifica al creador.
- `ImportacionPropuestaService` consulta primero propuestas oficiales y completa grupos desde `designaciones` legado cuando corresponde.

## Estados observados

El backend no usa una única máquina de estados. `propuestas.estado` contiene principalmente `borrador|oficial`; `propuesta_versiones.estado` contiene `pendiente|retirada|observada|aprobada`; la interfaz compone estados derivados (`propuesta`, `enviado`, `con_observaciones`, `oficial`). Las filas actuales y snapshots tienen estados propios. Ver [STATE_TRANSITIONS.md](STATE_TRANSITIONS.md).

## Archivos sin pipeline frontend

`resources/views/` contiene plantillas `.blade.php`; `resources/js/`, `package.json`, `vite.config.*` y `node_modules/` no existen. `resources/views/layouts/app.blade.php` carga Tailwind y Alpine desde CDN. `public/boceto-designaciones/` es prototipo estático, no frontend integrado al backend. Por eso no hay `package.json`: el proyecto no compila frontend con npm/Vite; Composer administra la aplicación PHP.

## Riesgo `public/storage`

`php artisan about --env=testing` reporta `public/storage NOT LINKED`. Existe ruta framework `storage/{path}` para lectura y escritura, pero la búsqueda de código no encontró `Storage::`, `UploadedFile`, `response()->download`, exportadores ni rutas de reportes. El flujo de designaciones no sube, descarga ni genera archivos actualmente; importación copia registros de base, no archivos. El riesgo afecta cualquier funcionalidad futura que dependa del disco público y merece prueba antes de habilitar cargas, descargas o reportes.

# Catálogo de endpoints

Catálogo obtenido de `php artisan route:list --json`, `routes/web.php`, `routes/auth.php` y `bootstrap/app.php`. Total: 24 rutas: 21 de aplicación, 2 de storage de Laravel y 1 health check. `web` aparece en todas las rutas web; se muestra middleware adicional relevante.

## Entrada y autenticación

| Método | URI | Acción | Middleware | Rol | Entrada | Efecto/tablas | Riesgo |
| --- | --- | --- | --- | --- | --- | --- | --- |
| GET/HEAD | `/` | Closure en `routes/web.php` | `web`, `auth` | Director o Vicerrectorado | Sesión | Redirige Vicerrectorado a `revisiones.pendientes`; demás a `designaciones.index`; sin escritura | P1 |
| GET/HEAD | `/login` | `AuthenticatedSessionController@create` | `web`, `guest` | Invitado | Ninguna | Renderiza login; sin escritura | P1 |
| POST | `/login` | `AuthenticatedSessionController@store` | `web`, `guest` | Invitado | `email`, `password`; rate limit 5 por email/IP en 60 s | Autentica, regenera sesión, redirige por rol; `users`, `sessions` | P0 |
| POST | `/logout` | `AuthenticatedSessionController@destroy` | `web`, `auth` | Usuario autenticado | Token CSRF | Cierra sesión, invalida sesión y regenera token; `sessions` | P0 |

## Director de carrera

Todas requieren `auth` + `rol:director_carrera`; las acciones de una propuesta pasan por `PropuestaPolicy`.

| Método | URI | Acción | Entrada | Efecto esperado / tablas | Riesgo |
| --- | --- | --- | --- | --- | --- |
| GET/HEAD | `/designaciones` | `PropuestaController@index` | Filtro opcional `gestion` | Lista propuestas de `user.carrera_id`; carga versiones, decisiones y conteos. Lectura de `propuestas`, `propuesta_versiones`, `propuesta_designaciones`, catálogos | P1 |
| POST | `/designaciones` | `PropuestaController@crear` | `gestion_id`, `periodo_id`, `descripcion` máx. 255 | Crea borrador de gestión actual; `propuestas` | P0 |
| POST | `/designaciones/copiar/previsualizar` | `previsualizarCopia` | Gestión/periodo fuente y destino | Calcula filas importables sin escribir; lee catálogos, propuestas y legado | P1 |
| POST | `/designaciones/copiar` | `copiar` | Mismos IDs | Crea borrador y aplica importación dentro de transacción; `propuestas`, `propuesta_designaciones`, `propuesta_eventos` | P0 |
| GET/HEAD | `/designaciones/{propuesta}` | `editar` | `{propuesta}` numérico | Verifica `view`; carga roster, versiones y observaciones; lectura amplia de catálogos y legado | P0 |
| PUT | `/designaciones/{propuesta}/asignaciones` | `guardar` | `changes[]`: grupo, materia, docente, horas pagadas/no pagadas, observación | Verifica `update`; actualiza/elimina filas actuales; valida malla y horas; `propuesta_designaciones`, eventos según servicio | P0 |
| POST | `/designaciones/{propuesta}/enviar` | `enviar` | `{propuesta}` | Verifica `send`; exige filas completas, crea versión secuencial y snapshots, registra evento, notifica Vicerrectorado; `propuestas`, filas, versiones, snapshots, eventos, `notifications` | P0 |
| GET/HEAD | `/designaciones/{propuesta}/importar` | `importar` | `{propuesta}` | Formulario de importación tras `update`; lectura de fuentes | P1 |
| POST | `/designaciones/{propuesta}/importar/previsualizar` | `previsualizarImportacion` | `gestion_id`, `periodo_id` fuente | Calcula `new/same/replaces`, sin escritura; lectura de propuesta/legado | P1 |
| POST | `/designaciones/{propuesta}/importar` | `aplicarImportacion` | IDs de fuente | Actualiza filas no bloqueadas, registra evento `importada`; `propuesta_designaciones`, `propuesta_eventos` | P0 |
| POST | `/designacion-versiones/{version}/retirar` | `retirar` | `{version}` | Solo remitente puede retirar versión pendiente; actualiza versión, registra evento y notifica Vicerrectorado; `propuesta_versiones`, `propuesta_eventos`, `notifications` | P0 |

## Vicerrectorado

Todas requieren `auth` + `rol:vicerrectorado`; revisión adicional pasa por `PropuestaVersionPolicy`.

| Método | URI | Acción | Entrada | Efecto esperado / tablas | Riesgo |
| --- | --- | --- | --- | --- | --- |
| GET/HEAD | `/revisiones/pendientes` | `RevisionPropuestaController@pendientes` | `folder` (`inbox/pendientes/revisadas/todas`), `q` | Lista y busca versiones; `pendiente` en bandeja, `aprobada/observada` revisadas; lectura de versiones/snapshots/usuarios/carreras | P0 |
| GET/HEAD | `/revisiones/{version}/revisar` | `revisar` | `{version}` numérico | Verifica `view`; carga snapshot/decisiones y determina `canDecide`; lectura | P0 |
| POST | `/revisiones/{version}/decidir` | `decidir` | `mode` (`approve_all/decide_rows`), observación general, decisiones por snapshot | Verifica `review`; crea decisiones, actualiza propuesta/filas, evento y notificación; `propuesta_version_decisiones`, versiones, propuestas, filas, eventos, `notifications` | P0 |

## Notificaciones

| Método | URI | Acción | Middleware | Rol permitido | Entrada | Efecto/tablas | Riesgo |
| --- | --- | --- | --- | --- | --- | --- | --- |
| GET/HEAD | `/notificaciones` | `NotificacionController@index` | `web`, `auth` | Cualquier usuario autenticado | Página opcional, paginación 20 | Lista notificaciones propias; `notifications` | P1 |
| POST | `/notificaciones/leer-todas` | `marcarTodasLeidas` | `web`, `auth` | Cualquier usuario autenticado | CSRF | Marca no leídas del usuario; `notifications.read_at` | P1 |
| POST | `/notificaciones/{notificacion}/leer` | `marcarLeida` | `web`, `auth` | Propietario del aviso | `{notificacion}`, CSRF | Verifica `notifiable_id/type`, marca una, normaliza URL legado y redirige; `notifications.read_at` | P0 |

## Rutas implícitas y salud

| Método | URI | Acción | Middleware | Entrada/efecto | Riesgo |
| --- | --- | --- | --- | --- | --- |
| GET/HEAD | `/storage/{path}` | Closure de `FilesystemServiceProvider` | Ninguno | Lee archivo del disco público configurado; no usa tabla | P0 si hay archivos sensibles |
| PUT | `/storage/{path}` | Closure de `FilesystemServiceProvider` | Ninguno | Escribe archivo en disco público configurado; no usa tabla | P0 |
| GET/HEAD | `/up` | Closure de `ApplicationBuilder` | Ninguno | Health check de Laravel; sin escritura | P2 |

## Observaciones de seguridad

- `EnsureRole` solo valida rol; aislamiento por carrera depende de Policies/servicios.
- Vicerrectorado tiene alcance global en código. `PropuestaVersionPolicy@view` no filtra carrera.
- Rutas storage no tienen `auth`; actualmente `public/storage` no está enlazado y no hay uso funcional de archivos.
- No se encontraron endpoints de API, Inertia, upload de archivos, descarga de reportes ni exportación.

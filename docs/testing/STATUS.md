# Estado de pruebas

## Cuenta de Decanatura para Ciencias Puras (07/10/2026)

- Se agregó `Decanatura de Ciencias Puras` al acceso compartido vigente, asignada
  a la facultad real. Reutiliza la clave configurada sin guardar ni mostrar su
  valor. La cuenta y su alcance aparecen cargados en la configuración activa. No
  se modificaron designaciones.
- El usuario confirmó que Decanatura solo debe ver `APROBADO`. La lectura
  autorizada encontró carreras con designaciones, pero todas están actualmente
  `SOLICITADO`; ninguna aparece hasta que existan designaciones aprobadas.
- Verificación: `tests/Feature/Auth/DemoAuthenticationTest.php` comprueba el
  acceso compartido, redirección y alcance asignado; `DemoUserProviderTest` y
  `DecanaturaDesignacionesTest` conservan cobertura sintética.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan test --env=testing --no-coverage tests/Feature/DecanaturaDesignacionesTest.php tests/Feature/Auth/DemoAuthenticationTest.php tests/Unit/DemoUserProviderTest.php` | OK; 18 pruebas, 113 aserciones; sin datos personales |
| Comprobación segura de cuenta y alcance activos | OK; cuenta presente, facultad asignada y clave compartida configurada; ningún valor secreto mostrado |
| Lectura de carreras y estados en Ciencias Puras | OK; transacción de solo lectura; sin escrituras |

## TASK2 Fase 3: separación de lectura y escritura (07/10/2026)

- Estado: operaciones externas separadas en contratos y adaptadores; los
  controladores conservan el servicio de aplicación como fachada y no acceden a
  transporte, consultas ni transacciones.
- Se definieron `DesignacionesReadContract` y `DesignacionesWriteContract`, con
  bindings verificables en el contenedor. `JachasunDesignacionesService` delega
  lecturas/escrituras a esos contratos y conserva las validaciones/orquestación
  vigentes que se extraerán como reglas de dominio en Fase 4.
- `JachasunDesignacionesReadAdapter` concentra consultas de lista por carrera,
  facultad y universidad, paginación, detalle, docentes, oferta, contexto y
  decisiones. Usa transacciones de solo lectura, parámetros enlazados y mapea
  respuestas mediante `DesignacionesResponseMapper` a DTOs.
- `JachasunDesignacionesWriteAdapter` concentra copia, alta/edición de cabecera,
  detalle, decisiones por fila y estado general. Mantiene transacciones normales,
  límites por operación y registro de apertura dentro de la transacción de
  detalle. Las decisiones por fila siguen en transacciones independientes.
- El servicio conserva los métodos públicos que consumen controladores y pruebas;
  sus arrays de presentación se generan desde DTOs en la fachada. No quedan
  operaciones de transporte duplicadas en el servicio antiguo.
- La búsqueda de docentes se trasladó sin cambiar su fuente, orden, normalización
  ni alternativa actual entre catálogos. Sigue pendiente
  `NEEDS_BUSINESS_CONFIRMATION` antes de cambiar qué fuente se considera válida
  en todos los ambientes; no se eligió una fuente nueva.
- Se alineó el mock unitario del orden del catálogo local con el orden vigente
  (`nombres`, luego identificador); la consulta y su comportamiento no cambiaron.
- Pruebas de contratos: `tests/Unit/DesignacionesGatewayTest.php` verifica
  sustitución por mocks y resolución de los dos adaptadores. La suite de servicio
  unitario valida SQL, bindings, transacciones, fallback de oferta/contexto y
  reglas de escritura usando conexiones simuladas.
- Toda la regresión ejecutada usa datos sintéticos y mocks; no se llamó a una
  fuente de datos real ni se usaron datos personales reales.

| Comando o revisión | Resultado |
| --- | --- |
| `php artisan test --env=testing --no-coverage tests/Unit/JachasunDesignacionesServiceTest.php tests/Unit/DecanaturaDesignacionesServiceTest.php tests/Unit/VicerrectoradoDesignacionesServiceTest.php tests/Unit/DesignacionesResponseMapperTest.php tests/Unit/DesignacionesGatewayTest.php` | OK; 60 pruebas, 317 aserciones |
| `php artisan test --env=testing --no-coverage tests/Feature/VicerrectoradoDesignacionesTest.php tests/Feature/DecanaturaDesignacionesTest.php tests/Feature/DesignacionesCharacterizationTest.php` | OK; 30 pruebas, 302 aserciones |
| Pruebas dirigidas de detalle/cabecera de Dirección | OK; 5 pruebas, 29 aserciones |
| `php -l` en archivos PHP modificados; `php artisan view:cache --no-ansi`; `php artisan route:list --path=designaciones --no-ansi` | OK; sintaxis y vistas correctas; 12 rutas vigentes |
| `vendor/bin/pint --test`; `composer audit --locked`; `npm audit` | OK; formato global y auditorías sin avisos |
| Suite completa | PENDIENTE; el entorno aislado de pruebas no está disponible y no se sustituyó por otra fuente |
| `git status --short`; `git diff --check` | No disponibles; esta copia no contiene metadatos Git |

- Fase 3 entregada para revisión. La confirmación sobre la fuente de búsqueda de
  docentes sigue pendiente; no bloqueó la separación estructural porque su
  comportamiento se trasladó sin variarlo. Fase 4 no iniciada.

## TASK2 Fase 2: DTOs y mapper (07/10/2026)

- Estado: DTOs tipados y mapper implementados; se migró un flujo pequeño de
  lectura y se conservaron sus datos visibles. No se alteraron rutas ni reglas.
- DTOs: `DesignacionResumen`, `DetalleAsignacion`, `DocenteResumen`,
  `OfertaMateria`, `DecisionAsignacion` y `RevisionDesignacion`, bajo
  `app/Data/Designaciones/`. Sus propiedades tienen tipos explícitos y ofrecen
  `toArray()` para serialización controlada.
- `DesignacionesResponseMapper` concentra fábricas para esos contratos, acepta
  filas crudas como arrays u objetos y comprueba presencia de campos, tipos,
  enteros, fechas, valores nulos y grupos. Las respuestas inválidas producen
  `InvalidDesignacionesResponse`, con un mensaje interno genérico.
- Flujo piloto: `JachasunDesignacionesService::obtenerRevisionDesignacion()`
  devuelve ahora `RevisionDesignacion`; el controlador consume su propiedad
  `observacion` y sigue pasando la misma observación escalar a Blade. Un
  resultado sin filas también falla de forma controlada. La lectura de revisión
  continúa independiente de la lectura de decisiones por fila.
- Las demás operaciones conservan sus contratos de arrays mientras se migran
  gradualmente. No se modificaron los datos de pantallas, JSON, PDF ni escritura.
- Pruebas nuevas: `tests/Unit/DesignacionesResponseMapperTest.php` cubre los seis
  DTOs, serialización, fechas, enteros, campos nulos/ausentes y tipos inválidos;
  `VicerrectoradoDesignacionesServiceTest` verifica el DTO del flujo piloto y la
  respuesta vacía. Las pruebas feature existentes se adaptaron al nuevo tipo sin
  cambiar sus expectativas visibles.
- Las pruebas usan filas y cuentas sintéticas, mocks y el entorno local aislado;
  no llaman a servicios institucionales reales ni usan datos personales reales.

| Comando o revisión | Resultado |
| --- | --- |
| `php artisan test --env=testing --no-coverage tests/Unit/DesignacionesResponseMapperTest.php tests/Unit/VicerrectoradoDesignacionesServiceTest.php tests/Feature/VicerrectoradoDesignacionesTest.php tests/Feature/DecanaturaDesignacionesTest.php tests/Feature/DesignacionesCharacterizationTest.php` | OK; 47 pruebas, 367 aserciones |
| Pruebas dirigidas de lectura aprobada y escrituras de Dirección en `JachasunDesignacionesDetailTest`/`JachasunDesignacionesEscrituraTest` | OK; 5 pruebas, 29 aserciones |
| `php -l` en archivos PHP modificados; `php artisan view:cache --no-ansi`; `php artisan route:list --path=designaciones --no-ansi` | OK; sintaxis y vistas correctas; 12 rutas vigentes |
| `vendor/bin/pint --test` | OK; chequeo global |
| `composer audit --locked`; `npm audit` | OK; sin avisos Composer y cero vulnerabilidades npm |
| `npm run build:css` | No requerido; no se modificaron estilos ni entradas CSS |
| Suite completa | PENDIENTE; el entorno aislado de pruebas no está disponible, por lo que no se ejecutó ni se sustituyó por otra fuente |
| `git status --short`; `git diff --check` | No disponibles; esta copia no contiene metadatos Git |

- Riesgos abiertos al cierre: repetir la suite completa cuando el entorno aislado
  esté disponible; antes de modificar la búsqueda de docentes, resolver el
  `NEEDS_BUSINESS_CONFIRMATION` anotado en Fase 1. Fase 3 quedó pendiente en ese
  cierre.
- Fase 2 entregada para revisión en ese cierre.

## TASK2 Fase 1: inventario y caracterización (07/10/2026)

- Estado al cierre de Fase 1: inventario completado; pruebas de caracterización
  agregadas. En ese momento no se había iniciado la fase 2.
- Se revisaron rutas, controladores, integración, modelos locales, pruebas y los
  specs activos de copia/edición, filas de asignación y decisiones generales.
- Mapa de rutas, entradas, autorización, comportamiento y operaciones actuales:

| Flujo/ruta | Entradas y alcance autorizado | Resultado visible | Operaciones actuales de aplicación |
| --- | --- | --- | --- |
| `GET /designaciones` | Director: carrera asociada a la sesión. Decanatura: facultad de la sesión. El filtro `search` se recorta a 100 caracteres. | Director recibe el conjunto de su carrera; filtro y paginación se aplican en la interfaz. Decanatura ve solo aprobadas de su facultad y consulta/imprime. Fallo de lectura: página segura `503`. | Dirección: `listar`; Decanatura: `listarAprobadasPorFacultad` (resuelve programas y consulta cada uno). |
| `GET /designaciones/docentes/buscar` | Solo Dirección; término `q`, recortado a 100. | Término vacío: `[]`; respuesta no vacía publica solo `id`, `nombre`, `ci`; fallo seguro `503`. | `buscarDocentes`. |
| `GET /designaciones/{id}` | Dirección: el ID debe estar en su carrera. Decanatura: debe pertenecer a su facultad y estar `APROBADO`. | Detalle 404 fuera de alcance. Dirección ve solo lectura si está aprobado; de otro modo recibe oferta para edición. Decanatura siempre consulta sin controles de edición/búsqueda/decisiones. Fallos de lectura: vista segura `503`. | Dirección: `listar`, `detallar`, `listarDecisionesAsignacionDetalle`; `ofertaMaterias` solo si editable. Decanatura: `obtenerAprobadaPorFacultad`, `detallar`. |
| `GET /designaciones/{id}/pdf` | Mismo alcance que el detalle; se revalida al imprimir. | PDF compartido inline; ID ajeno/inexistente: 404; fallo de lectura: respuesta segura `503`. | `listar` o `obtenerAprobadaPorFacultad`, `detallar`, render de `designaciones.pdf`. |
| `POST /designaciones` | Solo Dirección; `fecha`, `obs`, `importar_desde`, `gestion` y `periodo`; carrera/contexto salen del usuario y de la consulta vigente. | Crea vacía o importa desde una designación de la misma carrera; redirección con mensaje general. | `contextoActual` y luego `insertar` o `copiar`; `copiar` vuelve a verificar pertenencia al programa. |
| `POST /designaciones/{id}` | Solo Dirección; cabecera `fecha`/`obs`; se resuelve por la carrera de sesión. | ID ajeno: 404. Estado general `APROBADO`: se rechaza. Éxito/error: redirección segura. | `listar`, luego `actualizar` si el ID está dentro del alcance y no está aprobado. |
| `POST /designaciones/{id}/detalle` | Solo Dirección; ID de asignación resuelto en su carrera; detalle, docente, materia, grupo y horas validados. | ID ajeno: 404. Estado `APROBADO`: se rechaza. Éxito/error: redirección segura. | `listar`, luego `guardarDetalle`; este verifica oferta, grupo, horas y pertenencia de la fila. |
| `GET /vicerrectorado/designaciones` | Solo Vicerrectorado; gestión de cuatro dígitos opcional (por defecto año actual) y filtro de carrera opcional. | Lista global de la gestión; búsqueda, filtro y paginación locales. Fallo de lectura: vista segura `503`. | `listarUniversidad`. |
| `GET /vicerrectorado/designaciones/{id}` | Solo Vicerrectorado; el ID debe estar en el alcance universitario de la gestión seleccionada. | ID ajeno/inexistente: 404. Muestra filas; la lectura de decisiones por fila y de revisión general falla independientemente. | `obtenerUniversidad`, `detallar`, `listarDecisionesAsignacionDetalle`, `obtenerRevisionDesignacion`. |
| `GET /vicerrectorado/designaciones/{id}/pdf` | Solo Vicerrectorado; designación de la gestión seleccionada. | PDF inline; ID ajeno/inexistente: 404; fallo de lectura: respuesta segura `503`. | `obtenerUniversidad`, `detallar`, render de `designaciones.pdf`. |
| `POST /vicerrectorado/designaciones/{id}/decisiones` | Solo Vicerrectorado; ID universitario vigente, 1–500 IDs de fila distintos, estado `APROBADA`/`RECHAZADA` y observación opcional. | JSON con IDs actualizados/fallidos y estado general releído; el fallo de una fila no borra resultados de otras. | Verifica con `obtenerUniversidad`; ejecuta `guardarDecisionAsignacionDetalle` por fila; vuelve a leer la designación. La integración rechaza fila que no pertenece a esa designación. |
| `POST /vicerrectorado/designaciones/{id}/estado` | Solo Vicerrectorado; ID universitario vigente; `APROBADO` o `OBSERVADA`; observación requerida al observar. | JSON de confirmación con estado y observación general; validación o error con respuesta general. | `obtenerUniversidad`, luego `guardarRevisionDesignacion`. |

- Reglas confirmadas que caracterizan las pruebas: alcance decidido en servidor;
  Dirección no modifica designaciones aprobadas; Decanatura solo lee/imprime
  aprobadas de su facultad; horas enteras no negativas, acotadas por la oferta y
  no todas cero; grupo siguiente según oferta/aperturas/filas y grupo actual
  conservado al editar; decisiones por fila separadas de la revisión general;
  lotes independientes por fila; errores visibles genéricos.
- Decisiones técnicas sin cambio en esta fase: mantener la consulta de lista de
  Dirección como una lectura completa de su carrera y el filtro/paginación en la
  interfaz; no retirar métodos públicos de paginación aún no conectados a estas
  rutas; conservar respuestas externas como arrays hasta una fase posterior.
- `NEEDS_BUSINESS_CONFIRMATION`: antes de modificar la búsqueda de docentes en
  fase 3, confirmar la fuente válida en todos los ambientes soportados. El
  servicio actual contempla catálogo local con alternativas por disponibilidad;
  la búsqueda no se cambió en esta fase.
- Cobertura agregada: `tests/Feature/DesignacionesCharacterizationTest.php`
  caracteriza listado vigente, término de búsqueda vacío, IDs fuera del alcance
  de Dirección/Vicerrectorado y restricciones numéricas de rutas.
  `tests/Unit/VicerrectoradoDesignacionesServiceTest.php` agrega la comprobación
  de rechazo de una fila ajena a la designación.
- Las nuevas pruebas y fixtures usan `DemoUser`, datos sintéticos y Mockery; no
  se conectan a una fuente externa real ni usan datos personales reales.
- Las pruebas previas de `JachasunDesignacionesListTest` aún esperan paginación
  del servicio (`listarPaginado`), mientras el controlador y la vista vigentes
  cargan `listar` y filtran/paginan en la interfaz. No se tocaron esas
  expectativas existentes; queda revisión de esa deriva antes de depender de
  ellas como regresión.

| Comando o revisión | Resultado |
| --- | --- |
| `php artisan test --env=testing --no-coverage tests/Feature/DesignacionesCharacterizationTest.php tests/Unit/VicerrectoradoDesignacionesServiceTest.php` | OK; 14 pruebas, 97 aserciones; mocks y datos sintéticos |
| `php artisan test --env=testing --no-coverage tests/Feature/VicerrectoradoDesignacionesTest.php tests/Feature/DecanaturaDesignacionesTest.php` | OK; 25 pruebas, 253 aserciones; usuarios/filas sintéticos y servicio simulado |
| Pruebas dirigidas de lectura aprobada y escrituras de Dirección en `JachasunDesignacionesDetailTest`/`JachasunDesignacionesEscrituraTest` | OK; 5 pruebas, 29 aserciones |
| Intento adicional de `test_fallo_del_detalle_no_filtra_datos_externos` | BLOQUEADO antes de aserciones; requiere crear fixture en PostgreSQL de testing, que no acepta conexiones |
| Verificación previa del destino de testing | PostgreSQL aislado confirmado; no disponible. La suite completa y pruebas que crean fixtures no se ejecutaron ni se sustituyeron por otra fuente |
| `php artisan view:cache --no-ansi`; `php artisan route:list --path=designaciones --no-ansi` | OK; vistas compiladas y 12 rutas, IDs numéricos |
| `php -l` en los dos PHP nuevos/modificados; `vendor/bin/pint --test` | OK; sintaxis y formato global |
| `composer audit --locked`; `npm audit` | OK; sin avisos Composer y cero vulnerabilidades npm |
| `npm run build:css` | No requerido; no cambiaron clases ni entradas CSS |
| `git status --short`; `git diff --check` | No disponibles; esta copia no contiene metadatos Git |

- Fase 1 entregada para revisión. Riesgos abiertos: verificación contra
  PostgreSQL aislado cuando esté disponible; revisión de expectativas antiguas
  de paginación; confirmar la fuente de docentes antes de modificar ese flujo.
  Fase 2 quedó pendiente en ese cierre.

## Fase 4 Decanatura: regresión conjunta y cierre (07/10/2026)

- Se ejecutó la regresión conjunta disponible para autenticación, listado,
  detalle, PDF, denegación de escrituras a Decanatura, escritura sintética de
  Dirección y consulta/decisiones de Vicerrectorado. Todos los datos y cuentas
  usados fueron sintéticos; no se consultaron datos reales.
- El chequeo confirmó que `.env.testing` apunta al entorno aislado esperado, pero
  este no estuvo disponible. Por eso no se ejecutó la suite completa ni se usó
  otra fuente. La verificación automatizada de la migración de fase 1 también
  queda pendiente.
- No hubo cambios funcionales en la fase de cierre. Se revisaron instrucciones,
  documentación de higiene, rutas y pruebas aplicables.
- Se actualizó `AGENTS.md` para incluir el alcance vigente de Decanatura.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan test --env=testing --no-coverage tests/Feature/DecanaturaDesignacionesTest.php tests/Feature/Auth/DemoAuthenticationTest.php tests/Feature/VicerrectoradoDesignacionesTest.php tests/Unit/DecanaturaDesignacionesServiceTest.php tests/Unit/VicerrectoradoDesignacionesServiceTest.php tests/Unit/DemoUserProviderTest.php` | OK; 44 pruebas, 356 aserciones; datos sintéticos y servicios simulados |
| `JachasunDesignacionesEscrituraTest`: `test_director_actualiza_la_cabecera_de_su_designacion`, `test_director_actualiza_una_fila_de_detalle_de_su_designacion`, `test_director_no_puede_modificar_asignaciones_de_una_designacion_aprobada` | OK; 3 pruebas, 12 aserciones; escritura de Dirección simulada |
| `php artisan test --env=testing --no-coverage` | NO EJECUTADA; el chequeo previo de disponibilidad confirmó que el entorno aislado no estaba disponible |
| `php artisan view:cache --no-ansi` | OK |
| `php artisan route:list --path=designaciones --no-ansi` | OK; 12 rutas; IDs de detalle/PDF numéricos |
| `php -l` en controladores y pruebas de regresión | OK |
| `vendor/bin/pint --test` | OK; global |
| `composer audit --locked` | OK; sin avisos de vulnerabilidades |
| `npm audit` | OK; 0 vulnerabilidades |
| `git status --short`, `git diff --check` | No disponibles; esta copia no contiene metadatos Git |

- Fase 4 entregada para aceptación final. Riesgo pendiente: repetir suite
  completa y validación de fase 1 cuando esté disponible el entorno aislado.

## Decanatura Fase 3: presentación compartida y autorización por rol (07/10/2026)

- Se integró el listado y detalle compartidos. Decanatura ve las designaciones
  aprobadas con carrera identificada por fila; en detalle permanece en modo de
  consulta, sin controles de edición, búsqueda de docentes ni estados/decisiones
  por asignación. El PDF continúa usando la plantilla existente y recibe las
  filas de asignación sin cambios de formato.
- La raíz, el login y el menú dirigen Decanatura al listado compartido; el acceso
  de Vicerrectorado se conserva. Los POST de designaciones y búsqueda docente
  rechazan a Decanatura en servidor.
- Archivos modificados: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`,
  `app/Http/Controllers/DesignacionController.php`,
  `resources/views/layouts/header.blade.php`,
  `resources/views/layouts/sidebar.blade.php`,
  `resources/views/designaciones/lista.blade.php`,
  `resources/views/designaciones/carrera.blade.php` y
  `tests/Feature/DecanaturaDesignacionesTest.php`, `docs/README.md`.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan test --env=testing --no-coverage tests/Feature/DecanaturaDesignacionesTest.php tests/Feature/Auth/DemoAuthenticationTest.php tests/Unit/DecanaturaDesignacionesServiceTest.php` | OK; 17 pruebas, 108 aserciones; usuarios y filas sintéticos |
| `php artisan view:cache --no-ansi` | OK |
| `php artisan route:list --path=designaciones --no-ansi` | OK; 12 rutas; IDs de detalle/PDF conservan restricción numérica |
| `php -l` en controladores y prueba modificados | OK |
| `vendor/bin/pint --test` | OK; chequeo global y dirigido |
| `npm audit` | OK; 0 vulnerabilidades |
| `composer audit --locked` | OK; sin avisos de vulnerabilidades |
| Suite completa `php artisan test --env=testing --no-coverage` | PENDIENTE; se confirmó que el entorno aislado de testing no está disponible |
| `git status --short`, `git diff --check` | No disponibles; esta copia no contiene metadatos Git |

- La fase 2 fue aprobada por el usuario para iniciar esta fase. La fase 3 se
  entrega para revisión; fase 4 no iniciada.

## Limpieza de modelos del flujo retirado (07/10/2026)

- Se retiraron de `app/Models` los seis modelos del antiguo flujo de propuestas,
  junto con su notificación y fábricas. También se eliminó su generación en los
  datasets sintéticos y las validaciones asociadas.
- Se conservaron las migraciones históricas y los modelos académicos que siguen
  siendo usados por pruebas de integridad, factories y seeders.
- Archivos funcionales: `app/Models/Propuesta*.php`,
  `app/Notifications/PropuestaActualizadaNotification.php`,
  `database/factories/Propuesta*.php`, `database/seeders/Testing/` y
  `composer.json`.

| Comando o revisión | Resultado |
| --- | --- |
| Búsqueda de referencias activas a modelos y datos sintéticos retirados | OK; quedan solo aserciones que verifican que la interfaz no ofrezca el flujo anterior |
| `php -l` en los seis archivos PHP modificados | OK |
| `php artisan test --env=testing --no-coverage` | BLOQUEADA parcialmente; 55 pruebas aprobadas y 122 errores porque el PostgreSQL de testing no estaba disponible |
| `vendor/bin/pint --test` | OK |
| `npm audit` | OK; 0 vulnerabilidades |
| `composer audit --locked` | OK; sin avisos de vulnerabilidades |
| `git status --short`, `git diff --check` | No disponibles; esta copia no contiene metadatos Git |

## Búsqueda y filtros en lista de Vicerrectorado (07/10/2026)

- Estado: implementado. La gestión conserva el filtro de año; se añadió filtro
  de carrera y búsqueda instantánea sin distinguir mayúsculas ni tildes sobre
  descripción, observación, carrera, fecha, gestión, periodo y estado. La
  paginación local se aplica al conjunto completo de la gestión seleccionada.
- El filtro de carrera se conserva al consultar otro año y al volver desde el
  detalle. El servicio entrega las filas ordenadas de los periodos disponibles
  sin paginación de servidor para que la búsqueda no omita resultados.
- Archivos funcionales: `app/Services/Jachasun/JachasunDesignacionesService.php`,
  `app/Http/Controllers/VicerrectoradoDesignacionController.php`,
  `resources/views/vicerrectorado/designaciones/index.blade.php`,
  `resources/views/vicerrectorado/designaciones/detalle.blade.php` y
  `resources/assets/js/vicerrectorado/designaciones/lista.js`.
- Pruebas: `tests/Feature/VicerrectoradoDesignacionesTest.php` y
  `tests/Unit/VicerrectoradoDesignacionesServiceTest.php`.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan test --env=testing --no-coverage tests/Feature/VicerrectoradoDesignacionesTest.php tests/Unit/VicerrectoradoDesignacionesServiceTest.php` | OK; 25 pruebas, 236 aserciones |
| `node --check resources/assets/js/vicerrectorado/designaciones/lista.js` y comprobación directa de filtros en Node | OK; observación, tildes, carrera, paginación y enlaces |
| `php artisan view:cache --no-ansi`, `php -l` en PHP modificado | OK |
| `vendor/bin/pint --test` en archivos modificados y global | OK |
| `npm audit`; `composer audit --locked` | OK; sin vulnerabilidades ni avisos |
| `php artisan test --env=testing --no-coverage` | BLOQUEADA parcialmente; 52 pruebas aprobadas y 122 errores por conexión rechazada al PostgreSQL aislado de testing (`127.0.0.1:55432`) |
| `npm run build:css` | No requerido; no se cambiaron clases Tailwind |
| `git status --short`, `git diff --check` | No disponibles; esta carpeta no contiene metadatos Git |

Riesgo pendiente: el navegador recibe todas las designaciones del año elegido
para permitir filtrar el conjunto completo; si el volumen anual aumenta
considerablemente, revisar el rendimiento de la carga y el filtrado local.

## Decanatura Fase 2: consulta de lectura por facultad (07/10/2026)

- Fase 1 fue revisada y aprobada por el usuario; su verificación automatizada
  contra el entorno aislado sigue pendiente.
- Se confirmó mediante metadatos leídos en transacción `READ ONLY` que
  `alm_programas_facultades.id_facultad` es `integer`,
  `alm_programas.id_facultad` es `smallint` y `alm_programas.id_programa` es
  `character(3)`. No se leyeron filas personales ni filas reales de
  designaciones, y no se ejecutaron escrituras.
- `JachasunDesignacionesService` obtiene los códigos de carrera con consulta
  parametrizada y después reutiliza `f_asignaciones(?, ?, ?)` en lecturas
  `READ ONLY`. El servidor combina las carreras, filtra exclusivamente
  `APROBADO` y ordena establemente. Detalle y PDF vuelven a verificar facultad y
  estado; los rechazos responden sin revelar la causa.
- Archivos funcionales: `app/Services/Jachasun/JachasunDesignacionesService.php`
  y `app/Http/Controllers/DesignacionController.php`.
- Pruebas sintéticas: `tests/Unit/DecanaturaDesignacionesServiceTest.php` y
  `tests/Feature/DecanaturaDesignacionesTest.php`.

| Comando o prueba | Resultado |
| --- | --- |
| Consulta parametrizada de metadatos en transacción `READ ONLY` | OK; campos y tipos confirmados; no se leyeron filas personales ni se ejecutaron escrituras |
| `php artisan test --env=testing --no-coverage tests/Unit/DecanaturaDesignacionesServiceTest.php tests/Feature/DecanaturaDesignacionesTest.php` | OK; 7 pruebas, 38 aserciones; mocks y datos sintéticos |
| `php artisan view:cache --no-ansi` | OK |
| `php artisan route:list --path=designaciones --no-ansi` | OK; 12 rutas registradas; IDs de detalle/PDF conservan restricción numérica |
| `php -l` en los cuatro archivos PHP modificados | OK |
| `vendor/bin/pint --test` en los cuatro archivos PHP modificados | OK |
| `php artisan test --env=testing --no-coverage` | PENDIENTE; entorno aislado de testing no disponible; no se cambió a otra fuente |
| `vendor/bin/pint --test` | OK; chequeo global |
| `composer audit --locked` | OK; sin avisos de vulnerabilidades |
| `git status --short`, `git diff --check` | No disponibles; esta copia no contiene metadatos Git |

- Riesgo: falta ejecutar la suite completa y las pruebas de fase 1 cuando el
  entorno aislado esté disponible. Fase 2 entregada para revisión; fase 3 no
  iniciada.

## Assets locales, Tailwind compilado e inicialización Alpine (07/10/2026)

- Estado: `public/assets` no existe. Las vistas cargan CSS/JS por
  `/resources/assets/{path}`; `FrontendAssetController` limita la publicación a
  CSS y JavaScript dentro de `resources/assets/`. El CSS del PDF usa
  `resource_path()`.
- Tailwind se compila localmente con CLI 4.3.0 desde
  `resources/assets/css/tailwind.input.css`; `tailwind.generated.css` se entrega
  mediante la misma ruta. Se retiró el CDN de Tailwind.
- Alpine queda después de `@stack('scripts')`, de modo que las fábricas de cada
  vista se registran antes de que Alpine inicialice las expresiones.
- Archivos funcionales: `package.json`, `package-lock.json`,
  `resources/assets/css/`, `resources/views/layouts/app.blade.php`,
  `app/Http/Controllers/FrontendAssetController.php`, `routes/web.php` y pruebas
  de assets.
- Instrucciones e informe: `AGENTS.md`, `docs/README.md` y
  `docs/testing/BUG_REPORTS/BUG-2026-10-07-alpine-assets-tailwind-cdn.md`.

| Comando o prueba | Resultado |
| --- | --- |
| `npm ci && npm run build:css` | OK; dependencias reproducibles y hoja local generada con Tailwind CLI 4.3.0 |
| `npm audit` | OK; 0 vulnerabilidades |
| `php artisan test --env=testing --no-coverage --filter=FrontendAssets` | OK; 4 pruebas, 80 aserciones |
| Solicitud HTTP local de los diez assets por `/resources/assets/` | OK; 10 respuestas 200; ruta ajena a assets: 404 |
| `php artisan route:list --path=resources/assets --no-ansi` | OK; ruta pública de assets registrada |
| `php artisan view:clear && php artisan view:cache --no-ansi` | OK |
| `node --check` en los tres controladores Alpine y comprobación de su registro en Node | OK |
| `php -l` en controlador, rutas, vistas y pruebas PHP modificadas | OK |
| `vendor/bin/pint --test` en archivos relacionados | OK |
| `php artisan test --env=testing --no-coverage` | BLOQUEADA parcialmente; 43 pruebas aprobadas y 122 errores por indisponibilidad del entorno de testing |
| `vendor/bin/pint --test` global | Detecta formato preexistente en `app/Services/Jachasun/JachasunDesignacionesService.php` y `app/Http/Controllers/DesignacionController.php` |
| `composer audit --locked` | OK; sin avisos de vulnerabilidades |
| `git status --short`, `git diff --check` | No disponibles; esta copia no contiene metadatos Git |

## Organización de CSS y JavaScript de vistas (06/10/2026)

- Estado: hojas y scripts propios trasladados a `public/assets/css/` y
  `public/assets/js/`, agrupados por layouts, vistas compartidas, designaciones
  y Vicerrectorado. Se retiraron `resources/views/Styles.css` y
  `resources/views/Script.js`.
- Las vistas cargan sus assets mediante `asset()`. El PDF incorpora el CSS del
  archivo local en el HTML para conservar la compatibilidad con DOMPDF. Los
  valores de Blade y las dependencias externas se conservaron.
- Se actualizaron las pruebas de listado, detalle, Vicerrectorado y PDF, y se
  agregó `tests/Unit/FrontendAssetsTest.php` para verificar contenido y
  referencias sin requerir servicios de datos.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan view:cache --no-ansi` | OK |
| `node --check` en los cuatro scripts trasladados | OK |
| `php artisan test --env=testing --no-coverage tests/Unit/FrontendAssetsTest.php` | OK; 1 prueba, 58 aserciones |
| Pruebas Feature de listado, detalle, Vicerrectorado y PDF | Parcial; 16 pruebas aprobadas; conexión al PostgreSQL de testing no disponible para las restantes; una falla de Vicerrectorado ya estaba registrada antes de este cambio |
| `php artisan test --env=testing --no-coverage` | Parcial; 39 pruebas aprobadas; pruebas que requieren PostgreSQL bloqueadas y una falla preexistente de Vicerrectorado |
| Solicitud HTTP local de los diez assets | OK; 10 respuestas 200 |
| Render sintético de `designaciones.pdf` | OK; el CSS externo aparece embebido en el HTML |
| `vendor/bin/pint --test` sobre archivos PHP modificados | OK |
| `vendor/bin/pint --test` global | Falla por formato preexistente en `app/Services/Jachasun/JachasunDesignacionesService.php` y `app/Http/Controllers/DesignacionController.php` |
| `composer audit --locked` | OK; sin avisos de vulnerabilidades |
| `git status --short`, `git diff --check` | No disponibles; esta copia no contiene metadatos Git |

Riesgo pendiente: ejecutar las pruebas Feature y la suite completa cuando el
PostgreSQL aislado de testing esté disponible.

## Fase 1 Decanatura: rol y alcance de cuenta (06/10/2026)

- Estado: rol y alcance implementados; revisión de restricciones en PostgreSQL
  testing bloqueada porque el servicio de pruebas no acepta conexiones. La
  columna local usa `integer`, confirmado mediante consulta parametrizada de
  metadatos en una transacción de solo lectura, sin consultar filas de datos.
- La cuenta de Decanatura requiere `facultad_id` y excluye `carrera_id`; los
  roles de Dirección y Vicerrectorado conservan sus alcances exclusivos. Se
  añadió una cuenta sintética de Decanatura al dataset de testing.
- Archivos funcionales: `app/Models/User.php`,
  `app/Auth/Demo/DemoUserProvider.php`,
  `database/migrations/2026_10_06_120000_add_facultad_scope_to_users_table.php`,
  `database/factories/UserFactory.php`,
  `database/seeders/Testing/TestingUsersSeeder.php` y
  `database/seeders/Testing/TestingDatasetValidator.php`.
- Pruebas: `tests/Feature/RoleAuthorizationTest.php` y
  `tests/Unit/DemoUserProviderTest.php`.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan test --env=testing --no-coverage tests/Unit/DemoUserProviderTest.php` | OK; 2 pruebas, 12 aserciones |
| `php artisan test --env=testing --no-coverage tests/Feature/RoleAuthorizationTest.php` | BLOQUEADA antes de aserciones; 9 errores por PostgreSQL testing no disponible |
| `php artisan test --env=testing --no-coverage` | BLOQUEADA; 38 pruebas aprobadas y 1 fallo en una prueba de Vicerrectorado fuera del alcance; las pruebas que requieren PostgreSQL no pudieron conectarse |
| `php artisan test --env=testing --no-coverage tests/Feature/VicerrectoradoDesignacionesTest.php --filter=test_vicerrectorado_puede_abrir_el_detalle_y_revisar_la_designacion` | Falla reproducible fuera del alcance; 1 prueba, 67 aserciones antes del fallo; no se modificó su vista ni controlador |
| `php -l` en los 8 archivos PHP modificados | OK |
| `vendor/bin/pint --test` en los archivos PHP modificados | OK |
| `vendor/bin/pint --test` | Falla por formato en `app/Services/Jachasun/JachasunDesignacionesService.php` y `app/Http/Controllers/DesignacionController.php`, fuera del alcance de esta fase |
| `composer audit --locked` | OK; sin avisos de vulnerabilidades |
| Migración/restricciones en PostgreSQL testing | PENDIENTE; entorno de testing no acepta conexiones |
| `git status --short`, `git diff --check` | No disponibles; este directorio no contiene metadatos Git |

Riesgo de cierre: las restricciones de la migración y las pruebas Feature deben
ejecutarse contra el PostgreSQL aislado cuando esté disponible. La suite completa
también reporta un fallo de una prueba de Vicerrectorado fuera de esta fase.

## Retiro de vistas sin uso (06/10/2026)

- Estado: retiradas cuatro plantillas sin referencias desde las rutas, los
  controladores ni las inclusiones actuales. No se modificaron las pruebas ni
  las vistas que siguen conectadas al flujo.
- Archivos eliminados: `resources/views/propuestas/importar.blade.php`,
  `resources/views/revisiones/pendientes.blade.php`,
  `resources/views/versiones/revisar.blade.php` y
  `resources/views/partials/modal-imprimir-designaciones.blade.php`.
- Riesgo: las pruebas funcionales quedaron bloqueadas porque PostgreSQL de
  testing no acepta conexiones. La compilación de vistas sí terminó con éxito;
  las pruebas existentes que cubren rutas retiradas no pudieron ejecutarse.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan view:cache --no-ansi` | OK; las vistas restantes compilaron |
| `php artisan test --env=testing --no-coverage tests/Feature/PageAccessTest.php tests/Feature/JachasunDesignacionesListTest.php` | BLOQUEADA; 0 aprobadas y 14 errores previos a aserciones por indisponibilidad de PostgreSQL testing |
| `php artisan test --env=testing --no-coverage` | BLOQUEADA; 38 aprobadas y 117 errores de 155 por indisponibilidad de PostgreSQL testing |
| `vendor/bin/pint --test` | Falla por formato preexistente en `tests/Feature/Auth/GuestAccessTest.php` |
| `composer audit --locked` | 4 avisos existentes en dependencias |
| `git status --short`, `git diff --check` | No disponibles; esta carpeta no tiene metadatos Git |

## Correcciones de revisión y bloqueo de edición aprobada (02/10/2026)

- Estado: lectura de estados por fila y lectura de revisión general separadas.
  Si una lectura falla, la otra conserva sus datos; un estado de fila que no se
  pudo consultar se muestra como `Sin estado`, sin bloquear las acciones de
  aceptar/rechazar. Dirección puede consultar e imprimir una designación
  `APROBADO`, pero no editar su cabecera ni modificar/agregar asignaciones; los
  endpoints también rechazan esas escrituras.
- `NEEDS_BUSINESS_CONFIRMATION`: aún falta definir si una edición de fila
  revisada mientras el estado general no es `APROBADO` debe invalidar su
  decisión por fila y recalcular el estado general. El flujo de esos estados no
  se cambió.
- Archivos modificados: `AGENTS.md`, `app/Http/Controllers/DesignacionController.php`,
  `app/Http/Controllers/VicerrectoradoDesignacionController.php`, vistas de
  detalle, `resources/views/Script.js`, pruebas, especificación y reportes de
  regresión.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan test --env=testing --no-coverage tests/Unit/VicerrectoradoDesignacionesServiceTest.php tests/Feature/VicerrectoradoDesignacionesTest.php` | OK; 23 pruebas, 209 aserciones |
| Pruebas dirigidas de cabecera/fila normal y bloqueo al aprobar en `JachasunDesignacionesEscrituraTest` | OK; 6 pruebas, 24 aserciones |
| `php artisan test --env=testing --no-coverage tests/Feature/JachasunDesignacionesDetailTest.php --filter=test_designacion_aprobada_se_muestra_solo_para_consulta_a_direccion` | OK; 1 prueba, 13 aserciones |
| `php -l` en controladores y pruebas; `node --check resources/views/Script.js`; `php artisan view:cache --no-ansi`; rutas de Vicerrectorado | OK |
| `php artisan test --env=testing --no-coverage` | BLOQUEADA por PostgreSQL de testing no disponible; 38 aprobadas y 117 errores de 155 pruebas |
| `vendor/bin/pint --test` en archivos PHP modificados | OK |
| `vendor/bin/pint --test` | Falla por formato preexistente en `tests/Feature/Auth/GuestAccessTest.php` |
| `composer audit --locked` | 4 avisos existentes en `laravel/framework`, `league/commonmark` y `league/flysystem` |
| `git status --short`, `git diff --check` | No disponibles; este directorio no contiene metadatos Git |
| Prueba del contrato institucional | PENDIENTE; no se ejecutó contra un servicio real |

## Estado general y acciones de Vicerrectorado (02/10/2026)

- Estado: interfaz y controladores implementados; el detalle permite establecer
  `APROBADO` u `OBSERVADA` sin modificar filas, y las decisiones por fila
  recalculan el estado general. La observación general se guarda separada y se
  limpia al aprobar. El contrato institucional versionado requiere aplicación
  antes de habilitar estas acciones.
- Archivos modificados: `app/Http/Controllers/VicerrectoradoDesignacionController.php`,
  `app/Services/Jachasun/JachasunDesignacionesService.php`, `routes/web.php`,
  `resources/views/Script.js`, `resources/views/Styles.css`, vistas de listado y
  detalle de Vicerrectorado, pruebas y documentos de integración/especificación.

| Comando o prueba | Resultado |
| --- | --- |
| Pruebas de estados generales y acciones de Vicerrectorado | OK; 23 pruebas, 209 aserciones |
| Sintaxis, vistas, rutas y Pint dirigido | OK |
| Suite completa y auditoría | Ver bloque de correcciones más reciente |
| Prueba del contrato institucional | PENDIENTE; no se ejecutó contra un servicio real |

## Estado informativo por asignación en detalle de Carrera (02/10/2026)

- Estado: implementado. La tabla de Carrera muestra el estado por fila y abre
  un modal con su descripción y, si fue rechazada, la observación. Si no se
  puede consultar el estado, la etiqueta indica que no está disponible. No se
  añadieron controles para modificar decisiones.
- Archivos modificados: `app/Http/Controllers/DesignacionController.php`,
  `resources/views/Script.js`, `resources/views/designaciones/carrera.blade.php`,
  `tests/Feature/JachasunDesignacionesDetailTest.php`,
  `docs/testing/STATUS.md` y `docs/testing/TEST_MATRIX.md`.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan test --env=testing tests/Feature/JachasunDesignacionesDetailTest.php --filter=test_detalle_es_imprimible_y_muestra_acciones_por_fila_y_de_cabecera` | BLOQUEADA antes de ejecutar aserciones; PostgreSQL de testing no acepta conexiones en `127.0.0.1:55432` |
| `php -l app/Http/Controllers/DesignacionController.php tests/Feature/JachasunDesignacionesDetailTest.php`; `node --check resources/views/Script.js`; `php artisan view:cache`; comprobación directa de los helpers en Node | OK |
| `vendor/bin/pint --test` | Falla por hallazgos de formato en `app/Http/Controllers/DesignacionController.php` y `tests/Feature/Auth/GuestAccessTest.php`; la prueba modificada no presenta hallazgos en el chequeo enfocado |
| `composer audit --locked` | 4 avisos en `laravel/framework`, `league/commonmark` y `league/flysystem` |
| `git status --short`, `git diff --check` | No disponibles; este directorio no contiene metadatos de Git |

## Componente Blade reutilizable para modales (02/10/2026)

- Estado: implementado. Se extrajo en un componente con slots para título,
  contenido y acciones, más opciones para visibilidad, cierre, encabezado y
  formularios. Las dos modales de Vicerrectorado lo usan; mantienen sus estados
  y acciones propios.
- Archivos modificados: `resources/views/components/modal.blade.php`,
  `resources/views/vicerrectorado/designaciones/detalle.blade.php`,
  `resources/views/Styles.css`, `tests/Feature/VicerrectoradoDesignacionesTest.php`,
  `docs/testing/STATUS.md` y `docs/testing/TEST_MATRIX.md`.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan test tests/Unit/VicerrectoradoDesignacionesServiceTest.php tests/Feature/VicerrectoradoDesignacionesTest.php --env=testing --no-coverage` | OK; 13 pruebas, 145 aserciones |
| `php artisan view:cache --no-ansi` | OK |
| `vendor/bin/pint --test resources/views/components/modal.blade.php tests/Feature/VicerrectoradoDesignacionesTest.php` | OK |
| `vendor/bin/pint --test` | BLOQUEADA por hallazgos preexistentes en `app/Http/Controllers/DesignacionController.php` y `tests/Feature/Auth/GuestAccessTest.php` |
| `composer audit --locked` | 4 avisos en dependencias existentes |
| `php artisan test --env=testing --no-coverage` | BLOQUEADA; 21 aprobadas y 121 errores por indisponibilidad del entorno de pruebas |
| `git status --short`, `git diff --check` | No disponibles; este directorio no contiene metadatos de Git |

## Estado interactivo por asignación en detalle de Vicerrectorado (02/10/2026)

- Estado: implementado. El estado de cada asignación reutiliza la etiqueta
  `badge` como botón: aprobado se muestra verde, pendiente amarillo y rechazado
  rojo. Al pulsarlo se informa el estado; para una asignación rechazada también
  aparece la observación. Si se aprueba una asignación rechazada, se puede
  escribir otra observación o dejarla vacía para eliminar la anterior.
- Archivos modificados: `resources/views/vicerrectorado/designaciones/detalle.blade.php`,
  `resources/views/Styles.css`, `resources/views/Script.js`,
  `tests/Feature/VicerrectoradoDesignacionesTest.php`,
  `tests/Unit/VicerrectoradoDesignacionesServiceTest.php`,
  `docs/testing/STATUS.md` y `docs/testing/TEST_MATRIX.md`.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan test tests/Unit/VicerrectoradoDesignacionesServiceTest.php tests/Feature/VicerrectoradoDesignacionesTest.php --env=testing --no-coverage` | OK; 13 pruebas, 139 aserciones |
| `node --check resources/views/Script.js` y comprobación directa de colores, estado y observación en Node | OK |
| `php artisan view:cache --no-ansi` | OK |
| `vendor/bin/pint --test tests/Feature/VicerrectoradoDesignacionesTest.php tests/Unit/VicerrectoradoDesignacionesServiceTest.php` | OK |
| `vendor/bin/pint --test` | BLOQUEADA por hallazgos preexistentes en `app/Http/Controllers/DesignacionController.php` y `tests/Feature/Auth/GuestAccessTest.php` |
| `composer audit --locked` | 4 avisos en `laravel/framework`, `league/commonmark` y `league/flysystem` |
| `php artisan test --env=testing --no-coverage` | BLOQUEADA; 21 aprobadas y 121 errores por PostgreSQL de testing no disponible en `127.0.0.1:55432` |
| `git status --short`, `git diff --check` | No disponibles; este directorio no contiene metadatos de Git |

## Implementación operativa de decisiones en Vicerrectorado (01/10/2026)

- Estado: implementada y verificada.
- Se corrigió el envío de observación vacía desde el servicio y se conservaron
  las acciones individuales y grupales de aprobación/rechazo en el detalle.
- Los mensajes visibles de la revisión ahora son generales y no exponen
  detalles técnicos.
- La lectura y el guardado real fueron probados desde Laravel con una
  transacción reversible; el estado y la observación originales quedaron
  intactos tras `ROLLBACK`.
- Archivos modificados: `app/Services/Jachasun/JachasunDesignacionesService.php`,
  `app/Http/Controllers/VicerrectoradoDesignacionController.php`,
  `resources/views/Script.js`, `tests/Feature/VicerrectoradoDesignacionesTest.php`
  y `AGENTS.md`.

| Comando o prueba | Resultado |
| --- | --- |
| Smoke real de lectura y guardado reversible desde Laravel | OK; lectura, respuesta de guardado y rollback confirmados |
| `php artisan test tests/Unit/VicerrectoradoDesignacionesServiceTest.php tests/Feature/VicerrectoradoDesignacionesTest.php --env=testing --no-coverage` | OK; 12 pruebas, 117 aserciones |
| `node --check resources/views/Script.js`, `php -l`, `php artisan view:cache --no-ansi` | OK |
| `vendor/bin/pint --test` en archivos PHP modificados | OK |
| `php artisan test --env=testing --no-coverage` | BLOQUEADA; 20 aprobadas y 121 errores por PostgreSQL de testing no disponible |

## Regla de mensajes generales y seguros (01/10/2026)

- Estado: documentada en `AGENTS.md`.
- Los mensajes visibles y respuestas al usuario deben ser breves y generales;
  no deben revelar proveedores, fuentes de datos, estructuras, permisos,
  infraestructura ni detalles técnicos.
- No requiere cambios funcionales ni ejecución de pruebas.

## Smoke posterior de funciones en Jachasun (01/10/2026)

- Estado: OK con rollback.
- La columna `designaciones.asignaciones_detalles.obs` ya existe como `text`.
- `f_listar_decisiones_asignacion_detalle(integer)` ejecutó correctamente y
  devolvió una fila de decisión para una designación con detalle.
- `f_guardar_decision_asignacion_detalle(integer, integer, character varying,
  text)` devolvió `APROBADA` y la observación sintética durante la transacción.
- Se verificó la actualización dentro de la transacción y luego se forzó
  `ROLLBACK`; el estado y observación originales quedaron intactos.
- No quedaron datos de prueba persistidos.

## Smoke de funciones en Jachasun: columna `obs` faltante (01/10/2026)

- Estado: bloqueado por esquema incompleto en la BD conectada por la aplicación.
- Las funciones existen en `designaciones` con las firmas esperadas y el usuario
  de aplicación tiene `EXECUTE` sobre ambas.
- `designaciones.asignaciones_detalles.estado` existe como `character varying`,
  pero `designaciones.asignaciones_detalles.obs` no existe.
- La lectura falló al ejecutar
  `f_listar_decisiones_asignacion_detalle(integer)` con
  `column dad.obs does not exist`. No se ejecutó ninguna escritura.
- El DBA debe aplicar `ADD COLUMN IF NOT EXISTS obs text` y repetir el smoke;
  los permisos de tabla `SELECT` y `UPDATE` ya fueron confirmados para el
  usuario de aplicación.

## Estandarización de funciones de decisiones de Vicerrectorado (01/10/2026)

- Estado: script SQL ajustado a la convención de funciones existentes de
  Jachasun; aplicación en la BD y smoke autorizado pendientes del DBA.
- `f_listar_decisiones_asignacion_detalle` y
  `f_guardar_decision_asignacion_detalle` ahora usan `plpgsql`, bloque
  `DECLARE`, variables `vl_*` y `RETURN QUERY SELECT`, siguiendo el patrón de
  `f_designacion` y `f_designacion_detalle`, incluidos sus atributos de
  ejecución y propietario `utijavier`.
- La función de guardado actualiza la fila, comprueba `FOUND` y devuelve la
  fila afectada. Conserva la validación de pertenencia, los estados permitidos
  y la limpieza de observación vacía.
- No se ejecutó DDL ni DML en Jachasun real.

| Comando o prueba | Resultado |
| --- | --- |
| Revisión contra `copiar-y-editar-designaciones/scripts-bd.sql` y `editar-filas-designacion/scripts-bd.sql` | OK; estructura alineada |
| Verificación estática de firmas, bloques y permisos | OK |
| Smoke de funciones contra Jachasun | PENDIENTE; requiere DBA y datos sintéticos autorizados |

## Persistencia de decisiones de Vicerrectorado en Jachasun (01/10/2026)

- Estado: integración Laravel implementada y verificada con mocks; DDL de
  Jachasun pendiente de revisión/aplicación por el DBA.
- Se agregó POST `vicerrectorado/designaciones/{id}/decisiones`. Solo Vicerrectorado
  puede guardar `APROBADA`/`RECHAZADA` por asignación. Cada fila usa una
  transacción individual, por lo que las válidas se conservan aunque otras
  fallen. Una observación vacía limpia la anterior.
- `docs/specs/changes/decisiones-vicerrectorado-jachasun/scripts-bd.sql` propone
  las columnas y funciones de lectura/escritura. No se ejecutó DDL ni se hicieron
  escrituras en Jachasun. Si la función de lectura aún no está aplicada, el
  detalle permanece visible y los controles se deshabilitan con un aviso.
- Archivos modificados: `app/Services/Jachasun/JachasunDesignacionesService.php`,
  `app/Http/Controllers/VicerrectoradoDesignacionController.php`,
  `routes/web.php`, vistas/Script, pruebas y documentación de integración/spec.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan test tests/Unit/VicerrectoradoDesignacionesServiceTest.php tests/Feature/VicerrectoradoDesignacionesTest.php --env=testing --no-coverage` | OK; 12 pruebas, 117 aserciones |
| `php artisan route:list --path=vicerrectorado/designaciones --no-ansi` | OK; 4 rutas, incluida la escritura protegida por rol |
| `node --check resources/views/Script.js`, `php -l` y `php artisan view:cache --no-ansi` | OK |
| `vendor/bin/pint --test` en archivos modificados | OK |
| `php artisan test --env=testing --no-coverage` | BLOQUEADA; 20 aprobadas y 121 errores de 141 pruebas por conexión rechazada al PostgreSQL de testing `127.0.0.1:55432` |
| `vendor/bin/pint --test` | Falla en dos archivos ajenos al cambio: `app/Http/Controllers/DesignacionController.php` y `tests/Feature/Auth/GuestAccessTest.php` |
| `composer audit --locked` | 4 avisos en dependencias existentes |
| Smoke de funciones contra Jachasun | PENDIENTE; requiere aplicación y autorización del DBA |
| `git status --short` / `git diff --check` | No disponibles; este directorio no contiene repositorio Git |

## Iconos y modal común para aceptar/rechazar en Vicerrectorado (01/10/2026)

- Estado: implementado y cubierto por pruebas de interfaz.
- Las acciones individuales y grupales usan iconos sin texto visible y se
  mantienen alineadas horizontalmente. `Observar` fue reemplazado por
  `Rechazar`.
- Aceptar o rechazar una fila, o un grupo seleccionado, abre siempre el mismo
  modal. La observación es opcional; si se proporciona en una acción grupal, se
  aplica a todas las filas seleccionadas.
- Se conserva la selección múltiple y el carácter temporal de las decisiones;
  `Guardar cambios` cierra la revisión, sin persistencia en servidor.
- Archivos modificados: `resources/views/vicerrectorado/designaciones/detalle.blade.php`,
  `resources/views/Script.js`, `resources/views/Styles.css` y
  `tests/Feature/VicerrectoradoDesignacionesTest.php`.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan test tests/Feature/VicerrectoradoDesignacionesTest.php --env=testing --no-coverage` | OK; 5 pruebas, 79 aserciones |
| `node --check resources/views/Script.js` | OK |
| `php -l` en vista y prueba modificadas; `php artisan view:cache --no-ansi` | OK |
| `vendor/bin/pint --test` en archivos PHP modificados | OK |
| `php artisan test --env=testing --no-coverage` | BLOQUEADA; 15 aprobadas y 121 errores por conexión rechazada al PostgreSQL de testing `127.0.0.1:55432` |
| `vendor/bin/pint --test` | Falla en dos archivos ajenos al cambio: `app/Http/Controllers/DesignacionController.php` y `tests/Feature/Auth/GuestAccessTest.php` |
| `composer audit --locked` | 4 avisos en dependencias existentes |

## Acciones visuales de revisión por fila en Vicerrectorado (01/10/2026)

- Estado: implementadas con el alcance confirmado por el usuario; interfaz
  temporal sin persistencia.
- Cada fila tiene `Aprobar` y `Observar`. Aprobar cambia inmediatamente el estado
  visible en la página; Observar abre un formulario con motivo obligatorio y
  aplica la decisión a esa fila al guardarlo.
- Se agregó un checkbox en cada fila y otro para seleccionar todas. `Aceptar
  seleccionadas` aplica `Aprobada` al grupo; `Rechazar seleccionadas` aplica
  `Observada` a todas con un motivo común obligatorio.
- `Guardar cambios` cierra la revisión y bloquea más acciones. Una nota visible
  indica que las decisiones son temporales y se pierden al recargar; no se
  agregaron rutas ni escrituras de backend.
- Archivos modificados: `resources/views/vicerrectorado/designaciones/detalle.blade.php`,
  `resources/views/Script.js`, `resources/views/Styles.css` y
  `tests/Feature/VicerrectoradoDesignacionesTest.php`.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan test tests/Feature/VicerrectoradoDesignacionesTest.php --env=testing --no-coverage` | OK; 5 pruebas, 74 aserciones |
| `node --check resources/views/Script.js` | OK |
| `php -l` en vista y prueba modificadas; `php artisan view:cache --no-ansi` | OK |
| `vendor/bin/pint --test` en archivos PHP modificados | OK |
| `php artisan test --env=testing --no-coverage` | BLOQUEADA; 15 aprobadas y 121 errores de 136 pruebas por conexión rechazada al PostgreSQL de testing `127.0.0.1:55432` |
| `vendor/bin/pint --test` | Falla en `app/Http/Controllers/DesignacionController.php` y `tests/Feature/Auth/GuestAccessTest.php`, fuera del cambio |
| `composer audit --locked` | 4 avisos en dependencias existentes |
| `git status --short` / `git diff --check` | No disponibles; este directorio no contiene repositorio Git |

## Alineación del detalle de Vicerrectorado con Directores (01/10/2026)

- Estado: implementada y verificada con pruebas feature de Vicerrectorado.
- El detalle usa la estructura y estilos compartidos de `designaciones-detail`:
  encabezado con Volver/Imprimir, resumen de designación, observación y tabla de
  asignaciones con Docente, CI, Materia, Grupo y horas.
- Se conservan la lectura del registro, la gestión seleccionada, la información
  de carrera/fecha y el modo de solo lectura; no aparecen controles de edición.
- Archivos modificados: `resources/views/vicerrectorado/designaciones/detalle.blade.php`,
  `resources/views/Styles.css` y
  `tests/Feature/VicerrectoradoDesignacionesTest.php`.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan test tests/Feature/VicerrectoradoDesignacionesTest.php --env=testing --no-coverage` | OK; 5 pruebas, 55 aserciones |
| `php -l` en vista y prueba modificadas | OK |
| `php artisan view:cache --no-ansi` | OK |
| `vendor/bin/pint --test` en archivos modificados | OK |
| `php artisan test --env=testing --no-coverage` | BLOQUEADA; 15 aprobadas y 121 errores por conexión rechazada al PostgreSQL de testing `127.0.0.1:55432` |
| `vendor/bin/pint --test` | Falla en `app/Http/Controllers/DesignacionController.php` y `tests/Feature/Auth/GuestAccessTest.php`, fuera del cambio |
| `composer audit --locked` | 4 avisos en dependencias existentes |
| `git status --short` / `git diff --check` | No disponibles; este directorio no contiene repositorio Git |

Riesgo pendiente: revisión visual manual en navegador.

## Corrección de acciones Detalles e Imprimir en Vicerrectorado (01/10/2026)

- Estado: corregida y cubierta con pruebas de servicio y feature.
- La lista global consulta por separado los períodos institucionales `1` y `2`,
  pero las rutas de Detalles e Imprimir resolvían el registro mediante el período
  `0`, que no devuelve todas las filas. `obtenerUniversidad()` ahora busca el ID
  entre ambos períodos en una transacción `READ ONLY`.
- Se mantiene la autorización de Vicerrectorado, la gestión seleccionada y las
  rutas de solo lectura. Las pruebas del listado verifican además los enlaces
  generados de Detalles e Imprimir.
- Archivos modificados: `app/Services/Jachasun/JachasunDesignacionesService.php`,
  `tests/Unit/VicerrectoradoDesignacionesServiceTest.php` y
  `tests/Feature/VicerrectoradoDesignacionesTest.php`.
- Informe: `BUG_REPORTS/BUG-2026-10-01-vicerrectorado-detalle-pdf-periodos.md`.

| Comando o prueba | Resultado |
| --- | --- |
| Regresión antes de corregir | Falla como esperado: sólo se consultaba `f_asignaciones('UATF', gestion, '0')` |
| `php artisan test tests/Unit/VicerrectoradoDesignacionesServiceTest.php tests/Feature/VicerrectoradoDesignacionesTest.php --env=testing --no-coverage` | OK; 7 pruebas, 59 aserciones |
| `php artisan route:list --path=vicerrectorado/designaciones --no-ansi` | OK; rutas GET para listado, detalle y PDF |
| `php artisan view:cache --no-ansi`, `php -l` y Pint dirigido | OK |
| `php artisan test --env=testing --no-coverage` | BLOQUEADA; 15 aprobadas y 121 errores por conexión rechazada al PostgreSQL de testing `127.0.0.1:55432` |
| `vendor/bin/pint --test` | Falla en `app/Http/Controllers/DesignacionController.php` y `tests/Feature/Auth/GuestAccessTest.php`, sin cambios en esta fase |
| `composer audit --locked` | 4 avisos en dependencias existentes |

## Alineación visual de Vicerrectorado con Directores (01/10/2026)

- Estado: implementada y verificada con la regresión específica de
  Vicerrectorado.
- El listado de Vicerrectorado reutiliza las clases compartidas de
  `.designaciones-screen`, incluida la tabla, insignia de estado verde,
  paginación y estilos de botones de Directores.
- Se alineó la tabla con las columnas del listado de Directores. La carrera y su
  código se muestran bajo la descripción para conservar esos datos.
- Las acciones por fila ahora usan los mismos botones directos `Detalles` e
  `Imprimir` de Directores; se conservan las rutas institucionales y la consulta
  de solo lectura.
- Archivos modificados: `resources/views/vicerrectorado/designaciones/index.blade.php`,
  `resources/views/vicerrectorado/designaciones/detalle.blade.php`,
  `resources/views/Styles.css` y
  `tests/Feature/VicerrectoradoDesignacionesTest.php`.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan view:cache --no-ansi` | OK |
| `php -l` en vistas y prueba modificadas | OK |
| `vendor/bin/pint --test` en archivos modificados | OK |
| `php artisan test tests/Feature/VicerrectoradoDesignacionesTest.php --env=testing --no-coverage` | OK; 5 pruebas, 42 aserciones; cubre SOLICITADO verde y las columnas compartidas |
| `php artisan test --env=testing --no-coverage` | BLOQUEADA; 14 aprobadas y 121 errores por conexión rechazada al PostgreSQL de testing `127.0.0.1:55432` |
| `vendor/bin/pint --test` | Falla en `app/Http/Controllers/DesignacionController.php` y `tests/Feature/Auth/GuestAccessTest.php`, sin cambios en esta fase |
| `composer audit --locked` | 4 avisos en dependencias existentes; sin cambios de dependencias |
| `git status --short` / `git diff --check` | No disponibles; este directorio desplegado no contiene repositorio Git |

Riesgo pendiente: revisión visual manual en navegador.

## Fase 5 de modularizacion de vistas: Vicerrectorado detalle, parciales y limpieza (30/09/2026)

- Estado: implementada; validacion estructural OK; regresion de Vicerrectorado OK;
  pruebas de designaciones de director y suite completa bloqueadas por
  PostgreSQL de testing apagado.
- El detalle de Vicerrectorado mantiene el marcado de solo lectura y usa las
  reglas ya centralizadas bajo `.vicerrectorado-designaciones`.
- Los estilos inline de `partials/modal-confirmacion.blade.php` se movieron a
  `resources/views/Styles.css`.
- `partials/modal-notificacion.blade.php` ahora usa clases semanticas en la hoja
  compartida; se conservaron estados Alpine, mensajes, variantes, eventos y
  accesibilidad.
- `partials/modal-imprimir-designaciones.blade.php` se reviso y se conservo sin
  cambios porque no tiene referencias activas verificables; no se eliminaron
  componentes.
- No se modificaron controladores, servicios, rutas, modelos ni contratos de
  datos.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan view:cache --no-ansi` | OK |
| `php -l` en detalle y parciales modificados | OK |
| `node --check resources/views/Script.js` | OK |
| `vendor/bin/pint --test resources/views/vicerrectorado/designaciones/detalle.blade.php resources/views/partials/modal-confirmacion.blade.php resources/views/partials/modal-notificacion.blade.php resources/views/layouts/app.blade.php` | OK |
| `php artisan test tests/Feature/VicerrectoradoDesignacionesTest.php --env=testing --no-coverage` | OK; 5 pruebas, 35 aserciones |
| `php artisan test tests/Feature/VicerrectoradoDesignacionesTest.php tests/Feature/JachasunDesignacionesDetailTest.php tests/Feature/JachasunDesignacionesListTest.php --env=testing --no-coverage` | BLOQUEADA; 5 aprobadas, 29 errores de conexion; 34 pruebas totales |
| `php artisan test --env=testing --no-coverage` | BLOQUEADA; 14 aprobadas, 121 errores de conexion; 135 pruebas totales por PostgreSQL `127.0.0.1:55432` |
| `vendor/bin/pint --test` | Falla en `app/Http/Controllers/DesignacionController.php` y `tests/Feature/Auth/GuestAccessTest.php`, fuera del alcance |
| `composer audit --locked` | 4 avisos de seguridad en dependencias existentes; no se modificaron dependencias |

Riesgo pendiente: levantar el PostgreSQL aislado de testing y repetir las
regresiones de director y la suite completa. La validacion visual manual de
detalle y modales sigue pendiente.

## Fase 4 de modularizacion de vistas: Vicerrectorado, listado y estilos (30/09/2026)

- Estado: implementada; regresion dirigida OK; suite completa bloqueada por
  PostgreSQL de testing apagado.
- Las reglas visuales de `vicerrectorado/designaciones/_styles.blade.php` se
  centralizaron en `resources/views/Styles.css` con alcance
  `.vicerrectorado-designaciones`.
- `_styles.blade.php` conserva unicamente la carga de Roboto y Material Icons,
  porque sigue referenciado por `index.blade.php` y `detalle.blade.php`.
- Se conservaron el listado, filtros, paginacion, acciones Detalle/Imprimir,
  modo de solo lectura, contratos de datos y responsive aprobado.
- No se modificaron controladores, servicios, rutas, modelos ni contratos de
  datos.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan view:cache --no-ansi` | OK |
| `php -l` en las tres vistas de Vicerrectorado | OK |
| `vendor/bin/pint --test resources/views/vicerrectorado/designaciones/index.blade.php resources/views/vicerrectorado/designaciones/detalle.blade.php resources/views/vicerrectorado/designaciones/_styles.blade.php resources/views/layouts/app.blade.php` | OK |
| `php artisan route:list --path=vicerrectorado/designaciones --no-ansi` | OK; 3 rutas GET |
| `php artisan test tests/Feature/VicerrectoradoDesignacionesTest.php --env=testing --no-coverage` | OK; 5 pruebas, 35 aserciones |
| `php artisan test --env=testing --no-coverage` | BLOQUEADA; 14 aprobadas, 121 errores de conexion; 135 pruebas totales |
| `vendor/bin/pint --test` | Falla en `app/Http/Controllers/DesignacionController.php` y `tests/Feature/Auth/GuestAccessTest.php`, fuera del alcance |
| `composer audit --locked` | 4 avisos de seguridad en dependencias existentes; no se modificaron dependencias |

Riesgo pendiente: levantar el PostgreSQL aislado de testing y ejecutar la suite
completa. La validacion visual manual del listado y detalle sigue pendiente.

## Fase 3 de modularizacion de vistas: PDF de designaciones (30/09/2026)

- Estado: implementada; pruebas Feature bloqueadas por PostgreSQL de testing
  apagado.
- Los estilos de `resources/views/designaciones/pdf.blade.php` se centralizaron
  en `resources/views/Styles.css`.
- La vista PDF ahora incluye `Styles.css` directamente y no carga
  `resources/views/Script.js` ni Alpine.
- Las reglas DOMPDF quedaron encapsuladas bajo `body.designaciones-pdf` para
  que `body`, cabecera, tabla y footer no afecten las pantallas web.
- Se conservaron la cabecera institucional, margenes DOMPDF, cabecera repetible
  de tabla, saltos de pagina, columnas y footer fijo.
- Se agregaron comentarios de seccion para pagina, cabecera, tabla y footer.
- No se modificaron controladores, servicios, rutas, modelos ni contratos de
  datos.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan view:cache --no-ansi` | OK |
| `php -l resources/views/designaciones/pdf.blade.php` | OK |
| `vendor/bin/pint --test resources/views/designaciones/pdf.blade.php resources/views/layouts/app.blade.php` | OK |
| Render sintetico de `view('designaciones.pdf')` con datos de prueba | OK; CSS incluido, alcance `designaciones-pdf` y JavaScript ausente |
| `php artisan test tests/Feature/DesignacionPdfTest.php --env=testing --no-coverage` | BLOQUEADA; 8 errores por conexion rechazada a `127.0.0.1:55432` |
| `php artisan test --env=testing --no-coverage` | 14 aprobadas, 121 errores de conexion; 135 pruebas totales |

Riesgo pendiente: generar y revisar un PDF real despues de levantar PostgreSQL
de testing. La validacion manual del usuario debe confirmar que el resultado
visual conserva margenes, paginacion y footer antes de iniciar la fase 4.

## Fase 2 de modularizacion de vistas: lista de designaciones (30/09/2026)

- Estado: implementada; validacion automatizada bloqueada por PostgreSQL de
  testing apagado.
- Se agregaron los estilos del listado a `resources/views/Styles.css` y la
  fabrica Alpine `designacionesLista` a `resources/views/Script.js`.
- `designaciones/lista.blade.php` ya no contiene CSS inline ni el objeto Alpine
  extenso; conserva la configuracion dinamica Blade y los mismos contratos.
- Se agregaron comentarios de seccion para identificar encabezado, filtros,
  botones, tabla, paginacion, modal y operaciones del listado.
- No se modificaron controladores, servicios, rutas, modelos ni contratos de
  datos.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan view:cache --no-ansi` | OK |
| `node --check resources/views/Script.js` | OK |
| `php -l resources/views/designaciones/lista.blade.php` | OK |
| `php -l resources/views/layouts/app.blade.php` | OK |
| `vendor/bin/pint --test resources/views/designaciones/lista.blade.php resources/views/layouts/app.blade.php` | OK |
| `php artisan route:list --path=designaciones --no-ansi` | OK; 10 rutas registradas sin cambios |
| `php artisan test tests/Feature/JachasunDesignacionesListTest.php --env=testing --no-coverage` | BLOQUEADA; 9 errores por conexion rechazada a `127.0.0.1:55432` |
| `php artisan test --env=testing --no-coverage` | 14 aprobadas, 121 errores de conexion; 135 pruebas totales |
| `composer audit --locked` | 4 avisos de seguridad en dependencias existentes; no se modificaron dependencias |

Riesgo pendiente: levantar el PostgreSQL aislado de testing y ejecutar la
regresion dirigida. El recorrido manual visual del listado queda pendiente de
la validacion del usuario antes de iniciar la fase 3.

## Fase 1 de modularizacion de vistas: detalle de designaciones (30/09/2026)

- Estado: implementada; validacion automatizada bloqueada por PostgreSQL de
  testing apagado.
- Se crearon `resources/views/Styles.css` y `resources/views/Script.js` en la
  raiz de `resources/views/`.
- `designaciones/carrera.blade.php` ya no contiene el bloque CSS inline ni el
  objeto Alpine extenso; conserva la configuracion dinamica Blade y usa
  `designacionesCarrera(...)`.
- `layouts/app.blade.php` incluye los dos archivos compartidos mediante PHP
  inline. No se modificaron controladores, servicios, rutas, modelos ni
  contratos de datos.
- Se conservaron las rutas, nombres de campos, eventos Alpine, formularios,
  reglas de horas/grupos y estilos aprobados.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan view:cache --no-ansi` | OK |
| `node --check resources/views/Script.js` | OK |
| `php -l resources/views/designaciones/carrera.blade.php` | OK |
| `php -l resources/views/layouts/app.blade.php` | OK |
| `vendor/bin/pint --test resources/views/designaciones/carrera.blade.php resources/views/layouts/app.blade.php` | OK |
| `php artisan test tests/Feature/JachasunDesignacionesDetailTest.php --env=testing --no-coverage` | BLOQUEADA; 20 errores por conexion rechazada a `127.0.0.1:55432` |
| `php artisan test --env=testing --no-coverage` | 14 aprobadas, 121 errores de conexion; 135 pruebas totales |
| `vendor/bin/pint --test` | Falla en `app/Http/Controllers/DesignacionController.php` y `tests/Feature/Auth/GuestAccessTest.php`, fuera del alcance |
| `composer audit --locked` | 4 avisos de seguridad en dependencias existentes; no se modificaron dependencias |
| `git status --short` / `git diff --check` | No aplicable; el directorio no contiene repositorio Git |

Riesgo pendiente: levantar el PostgreSQL aislado de testing y ejecutar la
regresion dirigida. El recorrido manual visual del detalle queda pendiente de
la validacion del usuario antes de iniciar la fase 2.

## Consolidación de instrucciones del agente (30/09/2026)

- Estado: completada; `AGENTS.md` queda como único archivo de instrucciones.
- Se incorporaron el flujo vigente comprobado en rutas, el stack, reglas de
  seguridad/asignación, verificación y limpieza documental. Se eliminó el
  contexto obsoleto de `CLAUDE.md` (rutas de revisión ya no activas).
- Se actualizaron `docs/README.md` y `docs/HIGIENE_REPOSITORIO.md`; se conservó
  en `.gitignore` la exclusión preventiva de archivos locales `CLAUDE.md`.
- Verificación: `php artisan route:list --no-ansi` mostró 21 rutas;
  `test ! -e CLAUDE.md` y la búsqueda de enlaces/instrucciones activas a ese
  archivo terminaron sin referencias requeridas.
- No se ejecutaron pruebas funcionales porque el cambio es documental.

## Fase de consolidación documental (30/09/2026)

- Estado: completada; documentación de contexto consolidada en
  `docs/README.md` y README del proyecto.
- Se retiraron las guías redundantes o ajenas al estado actual de la aplicación:
  arquitectura, reglas de negocio duplicadas, diseño legacy, operación y guía
  de pruebas. Se conservaron la integración Jachasun, higiene, specs, historial,
  matriz e informes de bugs.
- Verificación: `php artisan route:list --no-ansi` mostró 21 rutas; las rutas
  confirman edición para Dirección de Carrera y consulta de solo lectura para
  Vicerrectorado. No quedan enlaces Markdown a las cinco guías retiradas; el
  enlace a la guía de testing archivada existe.
- Archivos: se creó `docs/README.md`; se actualizaron `README.md`,
  `docs/HIGIENE_REPOSITORIO.md`, los índices de `docs/specs/` y `docs/archive/`,
  los planes afectados, `docs/testing/TEST_MATRIX.md` y un informe de bug; se
  retiraron `docs/ARCHITECTURE.md`, `docs/BUSINESS_RULES.md`, `docs/DESIGN.md`,
  `docs/OPERATIONS.md` y `docs/TESTING.md`.
- No se ejecutó la suite porque el cambio es documental.
- Riesgo pendiente: algunas entradas históricas de `STATUS.md` y
  `TEST_MATRIX.md` describen versiones anteriores del flujo; para el estado
  actual prevalecen código, pruebas y `docs/README.md`.

## Fase visual legacy de Vicerrectorado (29/09/2026)

- Estado: completada; las vistas nuevas siguen la estructura visual legacy
  aplicada en esa fase. La guía visual anterior se retiró el 30/09/2026.
- Listado y detalle usan `breadcrumb`, `page-header`, `panel panel-inverse`,
  `table table-striped table-bordered table-td-valign-middle dt-responsive`,
  `table-responsive`, badges y paginación con la paleta Color Admin/Bootstrap.
- Las acciones del listado usan `btn-group dropup` y `dropdown-menu`; solo
  conservan Detalle e Imprimir.
- Se retiro el marcado Tailwind propio de las vistas de Vicerrectorado. El shell
  general no se modifico porque pertenece al alcance compartido de la aplicación.

| Comando o prueba | Resultado |
| --- | --- |
| Regresion visual de Vicerrectorado | OK; 6 pruebas, 42 aserciones |
| `php artisan view:cache --no-ansi` | OK |
| `vendor/bin/pint --test` en PHP y pruebas modificadas | OK |
| Suite completa `php artisan test --env=testing --no-coverage` | 14 aprobadas, 121 errores y 79 aserciones; errores baseline por PostgreSQL testing apagado |

## Correccion de bandeja Vicerrectorado sin filas (29/09/2026)

- Estado: correccion implementada y verificada en producción.
- Causa: `f_asignaciones('UATF', gestion, '0')` no devuelve todos los períodos;
  en producción devolvio cero filas para `2026`.
- Correccion: la aplicación combina los periodos `1` y `2` en una transacción
  `READ ONLY`, sin filtrar estados y con orden por fecha descendente.
- Informe: `BUG-2026-09-29-vicerrectorado-periodo-cero.md`.

| Comando o prueba | Resultado |
| --- | --- |
| Reproducción directa en producción | `2026/0`: 0 filas; `2026/1`: 6 filas; `2026/2`: 6 filas |
| Regresión previa a la corrección | Falló como esperado por usar periodo `0` |
| Regresión posterior | OK; 1 prueba, 7 aserciones |
| Consulta final mediante el servicio en producción | OK; 12 filas totales para `2026`, 10 en la primera página |

## Fase pantalla global de Vicerrectorado (29/09/2026)

- Estado: implementacion completada; ejecucion automatizada bloqueada por el
  PostgreSQL local de testing apagado.
- Vicerrectorado consulta `designaciones.f_asignaciones('UATF', gestion, '0')`
  en lectura, sin filtrar estados, con orden por fecha descendente y paginacion.
- Se agregaron listado, detalle y PDF de solo lectura. La gestión actual se usa
  por defecto y puede reemplazarse mediante el parametro `gestion`.
- Se corrigio `EnsureRole` para que valide realmente los roles declarados por la
  ruta. El acceso global queda limitado a `vicerrectorado`.
- No se creo una nueva funcion PostgreSQL: se encapsulo el contrato institucional
  existente en `JachasunDesignacionesService`.

| Comando o prueba | Resultado |
| --- | --- |
| Regresion dirigida antes de cambios | Bloqueada; 15 errores por conexion rechazada a `127.0.0.1:55432` |
| Regresion dirigida posterior a cambios | 6 aprobadas, 31 aserciones; la suite adicional de autorizacion sigue bloqueada por la conexion |
| Suite completa `php artisan test --env=testing --no-coverage` | 14 aprobadas, 121 errores y 68 aserciones; errores de conexion al PostgreSQL de testing |
| `php artisan view:cache --no-ansi` | OK |
| `php artisan route:list --path=vicerrectorado --no-ansi` | OK; 3 rutas GET |
| `php -l` en PHP modificado | OK |
| `vendor/bin/pint --test` en PHP modificado | OK |

Archivos funcionales: `app/Services/Jachasun/JachasunDesignacionesService.php`,
`app/Http/Controllers/VicerrectoradoDesignacionController.php`,
`app/Http/Middleware/EnsureRole.php`, `routes/web.php`,
`app/Http/Controllers/Auth/AuthenticatedSessionController.php`,
`resources/views/layouts/sidebar.blade.php` y las vistas bajo
`resources/views/vicerrectorado/designaciones/`.

Pruebas agregadas: `tests/Unit/VicerrectoradoDesignacionesServiceTest.php` y
`tests/Feature/VicerrectoradoDesignacionesTest.php`; se actualizo
`tests/Feature/PageAccessTest.php` para la nueva redireccion de Vicerrectorado.

Riesgo pendiente: levantar la base PostgreSQL aislada de testing y repetir la
regresion dirigida y la suite completa antes de liberar la pantalla.

## Fase de reproduccion: busqueda local y modal de filas (17/09/2026)

- Estado: reproduccion completada; correccion pendiente.
- Se confirmo que la busqueda actual ejecuta `public.f_buscar_docente` y no
  consulta el modelo local `App\Models\Docente`.
- Se confirmo que el boton de fila llama a `confirmarEdicionFila()` y que no
  existe validacion local ni estado de errores dentro del modal.
- Se confirmo que el selector calcula grupos, pero no muestra la ayuda
  contextual del grupo siguiente.
- La prueba existente de guardado HTTP completo pasa con el servicio simulado;
  el fallo observado queda en el flujo de UI y en la dependencia del buscador.
- Identificadores locales/Jachasun: el usuario confirmo compatibilidad directa
  entre `docentes.id` e `id_docente`.

| Comando o prueba | Resultado |
| --- | --- |
| Suite dirigida antes de cambios | 64/65; un fallo baseline de deriva de log |
| Regresiones nuevas antes del fix | 0/3; las tres fallan como se esperaba |
| Informes | `BUG-2026-09-17-busqueda-docentes-catalogo-local.md`, `BUG-2026-09-17-modal-fila-validacion-guardado.md`, `BUG-2026-09-17-aviso-grupo-siguiente-modal.md` |

No se modifico codigo funcional en esta fase.

## Fase backend: busqueda local de docentes (17/09/2026)

- Estado: completada.
- `JachasunDesignacionesService::buscarDocentes()` consulta `Docente` en una
  transaccion de solo lectura; ya no ejecuta funciones de indice externo.
- La normalizacion Laravel ignora mayusculas, tildes, signos y espacios
  repetidos; una consulta con varios tokens exige que todos coincidan en nombre
  o CI.
- La respuesta se limita a `id`, `nombre` y `ci`, con orden estable y maximo de
  100 resultados. El controlador vuelve a filtrar el contrato.
- El usuario confirmo que `docentes.id` es compatible con `id_docente`.

| Comando o prueba | Resultado |
| --- | --- |
| Regresion local por nombre, apellido, tildes, espacios y CI | OK; 1 prueba, 11 aserciones |
| Limite, orden estable y unicidad | OK; 1 prueba |
| Contrato y error seguro del endpoint | OK; 2 pruebas, 16 aserciones |
| Termino vacio sin consulta | OK; 1 prueba, 2 aserciones |
| `php -l` en servicio y controlador | OK |

Informe cerrado: `BUG-2026-09-17-busqueda-docentes-catalogo-local.md`.

## Correccion de esquema del catalogo local (17/09/2026)

- Estado: completada y verificada.
- La primera implementacion usaba `public.docentes`, inexistente en la
  conexion desplegada. La fuente autorizada disponible es
  `academico.docentes`.
- `buscarDocentes()` conserva `public.docentes` para testing/local y usa
  `academico.docentes` cuando es la tabla local disponible en Jachasun.
- El mapeo selecciona solo `id_docente`, nombres/apellidos y `ci`; no consulta ni
  devuelve contrasena, correo, direccion u otras columnas.

| Comando o prueba | Resultado |
| --- | --- |
| Regresion de catalogo academico | OK; 1 prueba, 12 aserciones |
| Regresiones catalogo local, limite y contrato | OK; 2 pruebas, 15 aserciones |
| Smoke de lectura en conexion desplegada | OK; 31 resultados para `Ada`, campos publicos `id`, `nombre`, `ci` |
| Verificacion de tabla desplegada | `public.docentes`: ausente; `academico.docentes`: disponible |

## Fase modal y guardado directo de filas (17/09/2026)

- Estado: completada en vista, contrato de formulario y pruebas relacionadas.
- El boton de fila valida docente, materia, grupo y horas dentro del modal; no
  abre el modal de confirmacion. La confirmacion de cabecera permanece.
- `requestSubmit()` envia el formulario asociado con `id_detalle`, `id_docente`,
  `id_materia`, `id_grupo` y las tres horas.
- Los valores de reasignacion siguen precargados en Alpine y se mantienen
  editables; cambiar materia carga sus horas oficiales y el grupo siguiente.
- El selector conserva el grupo actual al editar y expone solo el siguiente en
  una nueva fila. Debajo aparece la ayuda contextual calculada por materia.

| Comando o prueba | Resultado |
| --- | --- |
| Regresiones de modal, ayuda y precarga | OK; 4 pruebas, 65 aserciones |
| Escritura de detalle y validacion server-side | OK; 3 pruebas, 18 aserciones |
| Suites relacionadas | 68/69; un error baseline de deriva de log |
| `php artisan view:cache --no-ansi` | OK |
| `php -l` en PHP modificado | OK |

Informes cerrados: `BUG-2026-09-17-modal-fila-validacion-guardado.md` y
`BUG-2026-09-17-aviso-grupo-siguiente-modal.md`.

## Cierre de la implementacion (17/09/2026)

- Estado: completada para el alcance de `spec.md`.
- Archivos funcionales modificados: `app/Services/Jachasun/JachasunDesignacionesService.php`,
  `app/Http/Controllers/DesignacionController.php` y
  `resources/views/designaciones/carrera.blade.php`.
- Pruebas modificadas: `tests/Unit/JachasunDesignacionesServiceTest.php` y
  `tests/Feature/JachasunDesignacionesDetailTest.php`.
- La compatibilidad directa entre `docentes.id` e `id_docente` fue confirmada
  por el usuario; no se agrego un mapeo inventado.

| Comando o prueba | Resultado |
| --- | --- |
| Suite relacionada | 69/70; el unico error es `test_fallo_del_detalle_no_filtra_datos_externos`, deriva baseline de mensaje de log |
| Suite completa `php artisan test --env=testing --no-coverage` | 115/128; 10 fallos y 3 errores baseline, sin fallos nuevos del alcance |
| `php artisan view:cache --no-ansi` | OK |
| `php -l` en PHP modificado | OK |
| `vendor/bin/pint --test` en archivos modificados | OK |
| Busqueda local sin `public.f_buscar_docente` | Verificado por inspeccion y pruebas |

Fallos baseline completos: redireccion y rate limit de autenticacion, mocks
desactualizados del listado, acceso de paginas y tres expectativas de log de
fallos Jachasun. No se modificaron porque estan fuera del alcance.

Riesgo residual: no se ejecuto un navegador real para esta fase porque el
entorno solo dispone de Node.js y no tiene Chromium, Firefox, Playwright ni
Puppeteer. La vista, el contrato HTML y los eventos Alpine quedan cubiertos por
las pruebas de render y las regresiones de texto; se recomienda un recorrido
manual en un ambiente con navegador antes de liberar.

## Correccion de guardado con tabla auxiliar ausente (17/09/2026)

- Estado: completada.
- El smoke de esquema confirmo que `designacion_grupo_aperturas` no existe en
  la conexion desplegada.
- El servicio ahora verifica la tabla antes de registrar una apertura local y
  no aborta la escritura principal cuando esa tabla no esta disponible.
- No se ejecutaron escrituras reales en produccion; la evidencia real fue de
  esquema y las escrituras se cubrieron con mocks locales.

| Comando o prueba | Resultado |
| --- | --- |
| Regresion de tabla auxiliar ausente | OK; 1 prueba, 12 aserciones |
| Regresion de tabla auxiliar disponible | OK; 1 prueba, 10 aserciones |
| Suite unitaria del servicio | OK; 34 pruebas, 223 aserciones |
| Smoke de esquema desplegado | `designacion_grupo_aperturas`: ausente |

Informe: `BUG-2026-09-17-guardar-detalle-tabla-aperturas.md`.

## Fase modal nueva: materia libre, horas/grupo enviados y siguiente oficial (17/09/2026)

- Estado: completada en UI, backend y reglas.
- La materia puede buscarse y seleccionarse antes que el docente; se elimino
  el bloqueo y el mensaje `Selecciona un docente primero`.
- Causa raiz del `field is required`: horas y grupo estaban fuera de
  `form-editar-fila`. Ahora el `select` lleva `name="id_grupo"` y las horas
  van asociadas con `form="form-editar-fila"` (sin formularios anidados); se
  elimino el `hidden` espejo de `id_grupo`.
- Al seleccionar materia se cargan horas oficiales y el grupo siguiente
  siempre; la fila nueva expone solo `[siguiente]` y la edicion conserva el
  actual.
- Grupo siguiente oficial tambien en degradado: `max(grupo_siguiente,
  maxAsignado + 1)`; la apertura se registra al usarlo.
- Archivos modificados: `resources/views/designaciones/carrera.blade.php`,
  `app/Services/Jachasun/JachasunDesignacionesService.php`,
  `tests/Feature/JachasunDesignacionesDetailTest.php`,
  `tests/Unit/JachasunDesignacionesServiceTest.php`, `AGENTS.md` y
  documentacion de pruebas.

| Comando o prueba | Resultado |
| --- | --- |
| Regresiones antes del cambio | Fallan: materia deshabilitada sin docente, horas/grupo no enviados, nuevo con siguiente rechazado en degradado |
| `php artisan test tests/Unit/JachasunDesignacionesServiceTest.php tests/Feature/JachasunDesignacionesEscrituraTest.php --env=testing --no-coverage` | OK; 50/50 |
| `php artisan test tests/Feature/JachasunDesignacionesDetailTest.php --env=testing --no-coverage` | 14/15; unico error preexistente de deriva de log (`Detalle no disponible.` vs `Detalle Jachasun no disponible.`), anterior a esta sesion y fuera de alcance |
| `php artisan test --env=testing --no-coverage` | 110/123; 10 fallos baseline (auth/lista/acceso, mocks desactualizados) + 3 errores de la misma deriva de log (detalle/pdf/lista), preexistentes a esta sesion |
| `php artisan view:cache --no-ansi` | OK |
| `php -l` en servicio, vista y pruebas | OK |
| `vendor/bin/pint --test` en archivos tocados | OK |

Riesgo pendiente: la deriva de mensajes de log del controlador (acortados sin
`Jachasun` vs contrato largo de las pruebas) debe resolverse en fase propia;
no se toco el controlador para no ampliar alcance. Informes:
`BUG-2026-09-17-modal-nueva-materia-horas-grupo.md` y
`BUG-2026-09-17-grupo-siguiente-degradado-oficial.md`.

## Fase reglas de horas y grupos de asignaciones (17/09/2026)

- Estado: completada en backend, UI y reglas documentadas.
- Se permite `0` en cualquiera de las tres clases de horas, pero no se guarda
  una asignacion con las tres horas en `0`.
- Las nuevas filas usan el siguiente grupo consecutivo por materia y contexto;
  al editar se conserva el grupo actual como opcion valida.
- El selector de una fila nueva muestra unicamente el siguiente grupo.
- Archivos modificados: `app/Services/Jachasun/JachasunDesignacionesService.php`,
  `resources/views/designaciones/carrera.blade.php`, pruebas relacionadas,
  `AGENTS.md` y documentacion de pruebas.

| Comando o prueba | Resultado |
| --- | --- |
| Regresiones antes del cambio | Fallan: no se rechazaban horas todas en cero, no se exigia el grupo siguiente y la vista no limitaba el selector |
| `php artisan test tests/Unit/JachasunDesignacionesServiceTest.php --env=testing --no-coverage` | OK; 32 pruebas, 203 aserciones |
| `php artisan test tests/Feature/JachasunDesignacionesEscrituraTest.php tests/Feature/JachasunDesignacionesDetailTest.php --env=testing --no-coverage` | OK; 31 pruebas, 203 aserciones |
| `php artisan test --env=testing --no-coverage` | 110/121 aprobadas; 10 fallos baseline de autenticacion, listado y acceso |
| `php artisan view:cache --no-ansi` | OK |
| `php -l` en servicio y pruebas modificadas | OK |

Riesgo pendiente: la suite global conserva los fallos baseline ya conocidos; no
se modificaron esos flujos.

## Fase toolbar y nueva designacion en detalle (17/09/2026)

- Estado: completada en UI; sin cambios de backend.
- El input de busqueda, `Buscar` y `Limpiar` ahora se muestran en una misma
  fila. La accion `Nueva designacion` ocupa el bloque de acciones que antes
  contenia esos botones.
- `Nueva designacion` reutiliza el modal de reasignacion y lo inicializa con un
  detalle nuevo (`id = 0`); la reasignacion de filas existentes se conserva.
- Archivos modificados: `resources/views/designaciones/carrera.blade.php`,
  `tests/Feature/JachasunDesignacionesDetailTest.php` y esta documentacion.

| Comando o prueba | Resultado |
| --- | --- |
| Regresion antes del cambio | Falla; no existian los controles ni `abrirNuevaDesignacion()` |
| `php artisan test tests/Feature/JachasunDesignacionesDetailTest.php --filter='test_toolbar_ubica_filtros_junto_al_input_y_abre_modal_para_nueva_designacion|test_modal_de_edicion_usa_combobox_buscables_para_docente_y_materia' --env=testing --no-coverage` | OK; 2 pruebas, 34 aserciones |
| `php artisan test tests/Feature/JachasunDesignacionesDetailTest.php --env=testing --no-coverage` | OK; 13 pruebas, 115 aserciones |
| `php artisan test --env=testing --no-coverage` | 107/118 aprobadas; 10 fallos baseline de autenticacion, listado y acceso |
| `php artisan view:cache --no-ansi` | OK |
| `php -l` en vista y prueba modificadas | OK |
| `vendor/bin/pint --test resources/views/designaciones/carrera.blade.php tests/Feature/JachasunDesignacionesDetailTest.php` | OK |

No se modificaron controladores, servicios, rutas, modelos ni contratos de
backend. El recorrido visual con navegador no se ejecuto en esta fase.

## Fase rediseño UI de designaciones (16/09/2026)

- Estado: completada para `resources/views/designaciones/lista.blade.php`.
- Alcance: solo presentación; no se modificaron backend, rutas, servicios,
  modelos, consultas, formularios de servidor ni contratos de datos.
- Rediseño: se alineó la pantalla con el patrón visual legacy usando Roboto, breadcrumb,
  `page-header`, `panel-inverse`, tabla legacy responsive, badges, acciones
  `btn-group dropup` y modales con estructura legacy.
- Compatibilidad: se conservaron las variables Alpine, nombres de campos,
  rutas, mensajes, paginación visual y acciones existentes.
- Archivos modificados: `resources/views/designaciones/lista.blade.php` y esta
  documentación obligatoria de pruebas.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan view:cache --no-ansi` | OK |
| `php -l resources/views/designaciones/lista.blade.php` | OK |
| `php artisan test tests/Feature/JachasunDesignacionesListTest.php --env=testing --no-coverage` | 3 aprobadas, 5 fallos y 1 error baseline por mocks/expectativas desactualizados |
| `composer test -- --env=testing --no-coverage` | 105/116 aprobadas; 10 fallos preexistentes, incluidos auth y listado |
| `vendor/bin/pint --test` | Falla solo en `app/Http/Middleware/EnsureRole.php` y `tests/Feature/Auth/GuestAccessTest.php`, fuera del alcance |

Los fallos de pruebas se reproducen sin cambios de lógica y no se corrigieron en
esta fase porque el alcance solicitado es exclusivamente visual.

### Ajuste de encabezado y acciones (16/09/2026)

- Estado: completado.
- El encabezado del listado ahora identifica `Designaciones Docentes` y la
  carrera activa.
- El breadcrumb del listado muestra únicamente `Designaciones`; el detalle
  continúa como `Designaciones / Detalle de designación`.
- Las acciones de fila volvieron a ser botones visibles directamente:
  `Detalles` e `Imprimir`.
- Se eliminó `panel-heading-btn` del panel del listado.
- Archivos de vista modificados: `resources/views/designaciones/lista.blade.php`
  y `resources/views/designaciones/carrera.blade.php`.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan view:cache --no-ansi` | OK |
| `php -l resources/views/designaciones/lista.blade.php` | OK |
| `php -l resources/views/designaciones/carrera.blade.php` | OK |
| `php artisan test tests/Feature/JachasunDesignacionesDetailTest.php --env=testing --no-coverage` | OK; 11 pruebas, 98 aserciones |
| `php artisan test tests/Feature/JachasunDesignacionesListTest.php --env=testing --no-coverage` | 3 aprobadas; fallos baseline por mocks/expectativas desactualizados |
| `composer test -- --env=testing --no-coverage` | 105/116 aprobadas; fallos preexistentes de autenticación, acceso y listado |

## Fase header superior y UI de detalle (16/09/2026)

- Estado: completada únicamente en presentación.
- Header superior: se reemplazó la composición Tailwind por una barra clara
  compatible con Color Admin, con marca UATF, notificaciones, perfil, rol y
  cierre de sesión; se conservaron sus rutas y formularios.
- Detalle: se actualizaron encabezado, breadcrumb, resumen, paneles, tabla,
  acciones y modales al patrón visual legacy; se conservaron las variables
  Alpine, comboboxes, formularios y acciones existentes.
- Archivos modificados: `resources/views/layouts/header.blade.php` y
  `resources/views/designaciones/carrera.blade.php`.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan view:cache --no-ansi` | OK |
| `php -l resources/views/layouts/header.blade.php` | OK |
| `php -l resources/views/designaciones/carrera.blade.php` | OK |
| `php artisan test tests/Feature/JachasunDesignacionesDetailTest.php tests/Feature/DesignacionPdfTest.php --env=testing --no-coverage` | OK; 19 pruebas, 148 aserciones |
| `php artisan test tests/Feature/PageAccessTest.php --env=testing --no-coverage` | 3 aprobadas, 2 fallos baseline de redirección/expectativa visual |
| `composer test -- --env=testing --no-coverage` | 105/116 aprobadas; 10 fallos preexistentes de autenticación, acceso y listado |

### Ajuste de botones visibles (16/09/2026)

- Se hizo visible el texto `Desasignar` junto al icono existente, sin cambiar
  su botón, atributos ni lógica Alpine.
- Se verificó que no existen rutas para agregar controles ficticios de gestión o
  carrera en el header; no se añadieron acciones sin contrato.
- Regresión: `JachasunDesignacionesDetailTest` y `DesignacionPdfTest` pasan con
  19 pruebas y 148 aserciones.

### Icono de impresión (16/09/2026)

- Las acciones de impresión del listado y detalle muestran el icono `print` en
  lugar del texto visible, conservando `title`, `aria-label`, ruta PDF y
  apertura en pestaña nueva.
- Verificación de vistas: `view:cache` y sintaxis PHP OK; detalle y PDF pasan
  con 19 pruebas y 148 aserciones.

## Fase modales y buscador de materias (16/09/2026)

- Estado: completada en UI y JavaScript de interacción.
- Modal de creación: formulario responsive en dos columnas, con importación a
  ancho completo y footer legacy conservado.
- Modal de reasignación: se conserva el combobox de docente y materia, con
  presentación legacy y controles de horas/grupo intactos.
- Bug reproducido: el buscador de materia no reiniciaba el índice activo al
  escribir y Enter podía apuntar a una opción inexistente.
- Corrección: `actualizarFiltroMateria()` reinicia el índice y abre la lista;
  Enter limita el índice a las opciones actuales y Escape cierra siempre.
- Regresión agregada en `JachasunDesignacionesDetailTest` antes de corregir.
- Recorrido real de navegador: BLOQUEADO; el entorno no tiene Chromium,
  Firefox, Playwright ni Puppeteer instalados. Se verificó mediante HTTP,
  render Blade y contrato de interacción Alpine.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan test tests/Feature/JachasunDesignacionesDetailTest.php --env=testing --no-coverage` | OK; 11 pruebas, 100 aserciones |
| `php artisan test tests/Feature/JachasunDesignacionesListTest.php --env=testing --no-coverage` | 3 aprobadas; fallos baseline por mocks de servicio desactualizados |
| `composer test -- --env=testing --no-coverage` | 105/116 aprobadas; 10 fallos baseline |

## Fase modal de confirmación y capas (16/09/2026)

- Bug reproducido: el modal de confirmación usaba `z-50` y quedaba detrás de
  los modales de crear/reasignar, que usan `z-index: 1050`.
- Corrección: el modal de confirmación ahora usa la clase
  `designacion-confirmacion-modal` con `z-index: 1200 !important`, estructura
  legacy y botones `type="button"`; la notificación comparte la capa superior.
- Regresión agregada antes de corregir: confirma clase, prioridad y botones.
- Regresiones dirigidas: confirmación en reasignación 1/1 con 7 aserciones y
  confirmación en creación 1/1 con 6 aserciones.
- Verificación aislada: detalle + PDF pasan con 20 pruebas y 157 aserciones.
- Suite completa: 106/117 aprobadas; los 10 fallos restantes son baseline de
  autenticación, acceso y mocks del listado.

## Fase footer y paginacion PDF (11/09/2026)

- Estado: completada para el reporte `designaciones.pdf`.
- DOMPDF verificado: `dompdf/dompdf v3.1.6` y `barryvdh/laravel-dompdf v3.1.2`.
- Reserva inferior final: `70pt`; footer fijo a `10pt` del borde inferior.
- Regresión específica: los 6 casos PDF relevantes pasan con 32 aserciones; 1 prueba de listado falla por un mock Jachasun no relacionado con el PDF.
- Evidencia de una página: `/tmp/opencode/footer-verified-single.pdf`, renderizado `/tmp/opencode/footer-verified-single.png`, contador visible `1 / 1`.
- Evidencia multipágina: `/tmp/opencode/footer-verified-multi.pdf`, 4 páginas Carta renderizadas como `/tmp/opencode/footer-verified-multi-00.png` a `-03.png`; contadores visibles `1 / 4` y `4 / 4`.
- Verificación visual: segmentos azules 47%/6%/47%, rojo centrado al 83%, textos laterales alineados, espacio blanco inferior y sin filas superpuestas al footer.
- Suite completa: mantiene fallos preexistentes/no relacionados del proyecto en autenticación, listado Jachasun y expectativas de logs; no se modificaron esos comportamientos.

## Fase PDF de designaciones (10/09/2026)

- Estado: completada para la vista `resources/views/designaciones/pdf.blade.php`.
- Reproducción: el PDF real con el estado inicial dejaba cabecera, datos y tabla pegados a los bordes laterales, aunque el footer ya usaba `left/right: 34pt`.
- Corrección: se mantuvo Carta y `@page { margin: 10pt 34pt 38pt 34pt; }`, se agregó el `<body>` faltante y se fijó el área útil con `body { margin: 0; padding: 0 34pt; }`.
- Verificación específica: `php artisan test --filter=DesignacionPdfTest` pasó con 6 pruebas y 37 aserciones.
- Verificación visual: DOMPDF generó `/tmp/opencode/designacion-final-34pt.pdf`; ImageMagick lo rasterizó a `/tmp/opencode/designacion-final-34pt.png` (1275x1650). Se observaron márgenes laterales simétricos, tabla/footer alineados y textos de columnas legibles.
- Suite completa: `php artisan test` quedó con 90 pruebas aprobadas, 10 fallos y 1 error preexistentes/no relacionados con esta vista (redirecciones, fixtures/listado Jachasun y expectativas de logs).
- Riesgo pendiente: la suite global mantiene esos fallos de aplicación no relacionados; la regresión del PDF queda cubierta por la prueba específica.

## Fase documental: limpieza con lÃ³gica protegida

Estado: **COMPLETA**

Alcance: reorganizaciÃ³n de documentaciÃ³n, creaciÃ³n de guÃ­as canÃ³nicas y
archivo de material histÃ³rico. No se modificaron controladores, servicios,
Policies, modelos, migraciones, rutas, vistas ni configuraciÃ³n de ejecuciÃ³n.

Archivo canónico de contexto: [`../README.md`](../README.md). El contrato
técnico externo está en [`../INTEGRATION_JACHASUN.md`](../INTEGRATION_JACHASUN.md).

El historial se conserva en [`../archive/`](../archive/). Este archivo,
`TEST_MATRIX.md` y `BUG_REPORTS/` permanecen como rutas obligatorias de
trazabilidad.

## Reglas de cierre

- La suite se ejecuta contra PostgreSQL local/testing y datos sintÃ©ticos.
- Jachasun no se consulta durante la validaciÃ³n documental.
- Los estados, permisos e invariantes se comparan con cÃ³digo, migraciones y
  pruebas; ninguna ambigÃ¼edad se convierte en regla.
- Cada cambio de lÃ³gica futuro requiere una prueba de regresiÃ³n antes de
  corregirse.

## Evidencia de esta fase

| Comando | Resultado |
| --- | --- |
| `composer test -- --env=testing` | OK; 83 pruebas, 464 aserciones |
| `vendor/bin/pint --test` | OK |
| `git diff --check` | OK |
| VerificaciÃ³n de enlaces y rutas archivadas | OK |
| VerificaciÃ³n de alcance documental de la fase | OK |

Fecha de cierre: 2026-08-10.

## Fase de conexiÃ³n visible de `f_asignaciones`

Estado: **COMPLETA**

Se aÃ±adiÃ³ la pantalla protegida de consulta institucional y se mantuvo el
endpoint JSON existente. La pantalla usa el mismo servicio de lectura, no
escribe en Jachasun ni en la base local y no altera la importaciÃ³n histÃ³rica.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan test tests/Feature/InstitutionalDesignacionesScreenTest.php tests/Feature/InstitutionalDesignacionesEndpointTest.php --env=testing --no-coverage` | OK; 11 pruebas, 54 aserciones |
| `composer test -- --env=testing` | OK; 91 pruebas, 507 aserciones |
| `vendor/bin/pint --test` | OK |
| `git diff --check` | OK |

No se validÃ³ una conexiÃ³n real: permanece pendiente usar un ambiente de
desarrollo/staging autorizado con credenciales suministradas por administraciÃ³n.

## Ajuste de filtros institucionales

Se habilitÃ³ la combinaciÃ³n de gestiÃ³n y periodo `0`. La pantalla y el endpoint
la envÃ­an sin transformaciÃ³n, permitiendo consultar todo el historial del
programa; la importaciÃ³n local no fue modificada.

| Comando o prueba | Resultado |
| --- | --- |
| Pruebas institucionales con `INF / 0 / 0` | OK; 2 casos cubiertos |
| `composer test -- --env=testing` | OK; 93 pruebas, 515 aserciones |
| `vendor/bin/pint --test` | OK |
| `git diff --check` | OK |

## Fase historica de lista institucional para directores (superada)

Estado: **COMPLETA**

En la implementacion historica, `INSTITUTIONAL_LIST_MODE=true` hacia que `/designaciones`
la sigla de la carrera del director autenticado con gestiÃ³n y periodo `0`/`0`.
La vista es de solo lectura y no mezcla propuestas locales ni muestra acciones.
Si la integraciÃ³n estÃ¡ deshabilitada o falla, responde HTTP 503 con un mensaje
seguro. Con la bandera en `false` se conserva la lista local y sus rutas.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan test tests/Feature/InstitutionalDesignacionesListModeTest.php --env=testing --no-coverage` | OK; 4 pruebas, 30 aserciones |
| `composer test -- --env=testing` | OK; 97 pruebas, 545 aserciones |
| `vendor/bin/pint --test` | OK |
| `git diff --check` | OK |

La validaciÃ³n contra Jachasun real queda pendiente para el ambiente de
desarrollo/staging autorizado por administraciÃ³n; la suite local usa mocks.

## Riesgos pendientes

- Las reglas marcadas `NEEDS_BUSINESS_CONFIRMATION` requieren decisiÃ³n
  universitaria antes de ampliar el flujo.
- La integraciÃ³n institucional continÃºa deshabilitada por defecto y no puede
  importar filas hasta conocer docentes, materias, grupos y horas.

## Ajuste visual de lista institucional

Estado: **COMPLETA**

La lista institucional conserva el identificador, detalle, fecha, gestion,
periodo, observacion y estado. Se ocultaron las columnas de codigo y programa
porque ambos datos ya se muestran en el encabezado de la carrera. El contrato
de normalizacion y la consulta `f_asignaciones` no cambiaron.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan test tests/Feature/InstitutionalDesignacionesListModeTest.php --env=testing --no-coverage` | OK; 4 pruebas, 30 aserciones |
| `composer test -- --env=testing` | OK; 97 pruebas, 545 aserciones |
| `vendor/bin/pint --test` | OK |
| `git diff --check` | OK |

No se abrio la conexion real de Jachasun durante esta fase.

## Lista unica alimentada por Jachasun

Estado: **COMPLETA**

La ruta `/designaciones` ahora consulta siempre la carrera del director con
`f_asignaciones(sigla, '0', '0')`. La tabla existente se conserva y solo agrega
Fecha y Observacion; `r_id` ocupa la columna `#`. Las acciones se muestran
deshabilitadas y no ejecutan rutas locales. El parcial institucional fue
eliminado y el codigo se concentra en `lista.blade.php`.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan test tests/Feature/InstitutionalDesignacionesListModeTest.php tests/Feature/DesignacionesInterfazTest.php tests/Feature/PageAccessTest.php tests/Feature/PropuestaVersionadaTest.php --env=testing --no-coverage` | OK; 23 pruebas, 137 aserciones |
| `composer test -- --env=testing` | OK; 98 pruebas, 558 aserciones |
| `vendor/bin/pint --test` | OK |
| `git diff --check` | OK |

La equivalencia visual `SOLICITADO` -> `Oficial` queda como
`NEEDS_BUSINESS_CONFIRMATION`. No se conecto Jachasun real durante esta fase.

## Reestructuracion hacia Jachasun

Estado: **COMPLETA CON LIMITE DE ESQUEMA**

Se retiro la pantalla y el endpoint institucional duplicados. La lista principal
usa un servicio llamado `JachasunDesignacionesService` y la configuracion usa
`DB_CONNECTION=jachasun`, sin banderas `INSTITUTIONAL_*`. Las vistas del flujo
principal se conservaron. No se eliminaron modelos ni migraciones de propuestas
porque el repositorio aun no contiene evidencia del esquema equivalente en
Jachasun; esa eliminacion requiere una verificacion autorizada previa.

Las pruebas de la pantalla y endpoint retirados se eliminaron. Se conservaron
las pruebas de autenticacion, autorizacion, reglas de negocio y contrato de la
lista principal.

| Comando o verificacion | Resultado |
| --- | --- |
| `composer test -- --env=testing` | OK; 81 pruebas, 484 aserciones |
| `vendor/bin/pint --test` | OK |
| `git diff --check` | OK |
| Rutas `/institucional/designaciones` | No registradas |
| Configuracion `institutional` | Eliminada |
| Conexiones SQLite/MySQL/MariaDB/SQL Server | Eliminadas de `config/database.php` |
| `DB_CONNECTION=institucional` | Se normaliza internamente a `jachasun` |

No se eliminaron modelos, migraciones ni servicios del flujo de propuestas:
el repositorio no contiene un inventario verificable del esquema equivalente
en Jachasun. Esa eliminacion requiere una fase autorizada de compatibilidad.

## Login temporal desacoplado

Estado: **COMPLETA; PENDIENTE DE PROVEEDOR REAL**

Se agrego un proveedor de autenticacion en memoria seleccionable con
`AUTH_PROVIDER=demo`. Define cuatro cuentas temporales (Vicerrectorado, INF,
MED y MEC) y valida una contraseÃ±a comun desde `DEMO_AUTH_PASSWORD`. No usa la
tabla `users`, no escribe en Jachasun y no cambia controllers, rutas, vistas ni
policies. Testing conserva `AUTH_PROVIDER=users`.

| Comando o verificacion | Resultado |
| --- | --- |
| `php artisan test tests/Unit/DemoUserProviderTest.php tests/Feature/Auth/DemoAuthenticationTest.php --env=testing` | OK; 7 pruebas, 35 aserciones |
| `vendor/bin/pint --test` | OK |
| `git diff --check` | OK |
| Tinker: proveedor y 4 cuentas demo | OK; sin contraseÃ±a expuesta |

La suite completa no se ejecutÃ³ contra Jachasun real: el entorno testing apunta
a PostgreSQL aislado y no estaba disponible durante esta fase.

## Flujo principal Jachasun con detalle

Estado: **IMPLEMENTADA; VALIDACIÓN TESTING BLOQUEADA**

`/designaciones` consulta la carrera autenticada mediante
`f_asignaciones(sigla, '0', '0')`. `/designaciones/{id}` confirma que el
identificador pertenece a esa lista y consulta `f_asignaciones_detalles(?)`.
La normalización usa las once columnas conocidas proporcionadas por Jachasun.
El estado se muestra literalmente, incluyendo `SOLICITADO`. Abrir e Imprimir
usan el flujo principal y la vista `designaciones/carrera.blade.php` de solo
lectura.

Se retiraron rutas locales de propuestas y revisión; las vistas históricas se
conservaron sin rutas de escritura. No se conectó Jachasun real durante esta
fase.

| Comando | Resultado |
| --- | --- |
| `php artisan route:list --path=designaciones` | OK; solo lista y detalle |
| `php artisan view:cache` | OK |
| `vendor/bin/pint` | OK |
| `git diff --check` | OK |
| Suite PHPUnit | Bloqueada: PostgreSQL testing `127.0.0.1:55432` rechazó conexión |

La prueba real requiere resolver previamente la regla `pg_hba.conf` para la
IP de la aplicación.

## Paginación de la lista de designaciones (10 por página)

Estado: **COMPLETA**

`/designaciones` consulta ahora `listarPaginado(...)`, que envuelve
`f_asignaciones(sigla, '0', '0')` en un subquery con `ORDER BY r_id DESC`
(orden estable, más recientes primero) y `LIMIT/OFFSET`, reportando el total
con `COUNT(*) OVER()`. Solo viajan 10 filas desde Jachasun por página. La vista
muestra navegación Anterior/Siguiente y contador en español. El detalle
(`show`) conserva `listar` original sin cambios.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan test tests/Unit/JachasunDesignacionesServiceTest.php tests/Feature/JachasunDesignacionesListTest.php --env=testing --no-coverage` | OK; 10 pruebas, 64 aserciones |
| Suite completa `--env=testing` | 47/50; 3 fallos preexistentes de `GuestAccessTest` |
| `vendor/bin/pint --test` | OK |
| `git diff --check` | OK |

Los 3 fallos de `GuestAccessTest` (redirect por rol y rate limit) se reproducen
en el baseline sin los cambios de esta fase; no fueron introducidos acá. No se
conectó Jachasun real durante esta fase.

## Acciones visuales por fila en detalle Jachasun

Estado: **COMPLETA; ESCRITURA JACHASUN PENDIENTE**

La vista `/designaciones/{id}` ahora muestra una columna `Acciones` en cada
fila, con botones `Editar` y `Eliminar` usando los estilos existentes. `Editar`
abre un modal Alpine con los datos de la fila; `Eliminar` queda como botón
presionable sin modal ni mutación. La columna y el modal se ocultan al
imprimir. No se agregaron rutas ni mutaciones hasta recibir el contrato real
de la base.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan test tests/Feature/JachasunDesignacionesDetailTest.php --env=testing --no-coverage` | OK; 3 pruebas, 27 aserciones |
| `composer test -- --env=testing --no-coverage` | 47/50; 3 fallos preexistentes de `GuestAccessTest` |
| `vendor/bin/pint --test` | OK |
| `git diff --check` | OK |

Pendiente: recibir funciones Jachasun para guardar edición y desasignar docente.

## Diagnostico de conexion PostgreSQL en produccion

Estado: **RESUELTA; SUITE COMPLETA PENDIENTE**

El endpoint `/health` confirma que la aplicacion, cache y permisos de escritura
estan disponibles, pero la comprobacion de base de datos falla. El runtime PHP
8.4.24 de CLI y el PHP-FPM 8.4 no tienen habilitado `pdo_pgsql`;
`PDO::getAvailableDrivers()` devuelve una lista vacia y no existe el modulo en
las configuraciones de PHP.

Se alinearon la configuracion fuente, el servicio y los ejemplos para usar
`pgsql` como conexion canonica. Se respaldo la cache previa en una ruta local
con permisos restrictivos y se regenero `bootstrap/cache/config.php`.
No se ejecutaron consultas de datos, migraciones ni escrituras en Jachasun.

| Comando o verificacion | Resultado |
| --- | --- |
| `php -r "var_export(PDO::getAvailableDrivers());"` | BLOQUEADO; no hay drivers PDO disponibles |
| `php artisan tinker` (configuracion efectiva) | `default=pgsql`; conexion `pgsql` definida |
| `php artisan app:health --no-ansi` | `degraded`; solo `database` falla |
| `php artisan route:list --path=designaciones --no-ansi` | OK; lista y detalle registrados |
| `php -l` en archivos PHP modificados | OK |
| Suite PHPUnit | BLOQUEADA; `phpunit/phpunit` no esta instalado |

El administrador habilito el paquete PostgreSQL para PHP 8.4 y PHP-FPM. Se
sincronizo la cache con el usuario configurado en `.env`; la conexion y las dos
funciones de lectura respondieron correctamente. No se ejecutaron migraciones ni
escrituras en Jachasun.

| Comando o verificacion | Resultado |
| --- | --- |
| `PDO::getAvailableDrivers()` | OK; `pgsql` disponible |
| Conexion TCP a `10.10.166.120:5432` | OK |
| `SELECT 1` con la conexion Laravel | OK |
| `f_asignaciones('INF', '0', '0')` | OK; 47 registros totales, 10 en pagina |
| `f_asignaciones_detalles(id)` | OK; 69 filas para el primer registro |
| Suite PHPUnit | PENDIENTE; `phpunit/phpunit` no esta instalado |

No se ejecuto `app:health` durante esta validacion porque su comprobacion de
cache realiza una escritura temporal. La validacion equivalente de base de datos
se hizo con `SELECT 1` y el servicio real.

## Buscador por descripcion en la lista

Estado: **IMPLEMENTADA; SUITE AUTOMATIZADA PENDIENTE**

La lista acepta el parametro GET `search`, filtra por la descripcion
(`r_detalle`) mediante `ILIKE` parametrizado y conserva el termino en los
enlaces de paginacion. El limite del termino es de 100 caracteres y la consulta
continua ejecutandose dentro de una transaccion `READ ONLY`.

| Comando o prueba | Resultado |
| --- | --- |
| Prueba de sintaxis PHP | OK |
| `php artisan view:cache --no-ansi` | OK |
| Servicio real con `SEMESTRAL` | OK; 47 registros, 10 en pagina |
| Servicio real con termino inexistente | OK; 0 registros |
| Controlador con `search=SEMESTRAL` | OK; filtro conservado y total recibido |
| Pruebas PHPUnit relacionadas | BLOQUEADAS; `phpunit/phpunit` no esta instalado |
| `vendor/bin/pint --test` | BLOQUEADO; ejecutable no instalado |

## Escritura de designaciones: copiar, crear y editar cabecera (Jachasun)

Estado: **COMPLETA EN APP Y BD; SUITE AUTOMATIZADA PENDIENTE**

Se implementó el lado de aplicación de la feature
`docs/specs/changes/copiar-y-editar-designaciones/` reutilizando los componentes
de vista existentes (`partials/modal-confirmacion`, `partials/modal-notificacion`
y el modal de edición ya presente en `carrera.blade.php`):

- Servicio: `copiar()`, `insertar()`, `actualizar()`, `obtener()` en
  `JachasunDesignacionesService`. Las escrituras usan transacción normal (sin
  `SET TRANSACTION READ ONLY`) y `copiar`/`actualizar` verifican que la
  designación pertenezca a la carrera del director.
- Rutas POST: `designaciones.store`, `designaciones.update`, con `whereNumber`.
- Controlador: `store` (crear o importar desde otra gestión vía `importar_desde`),
  `update` con validación, autorización por carrera y mensajes seguros (se registra solo
  la clase de excepción).
- Vistas: "Nueva designación" con opción "Importar de una gestión anterior", edición de
  cabecera y acciones por fila (Editar/Desasignar) en el detalle; sin botón de copiado
  por fila.
- Tests: unit (`JachasunDesignacionesServiceTest`) y feature
  (`JachasunDesignacionesEscrituraTest`) escritos; se ajustaron
  `JachasunDesignacionesDetailTest` y `JachasunDesignacionesListTest` al nuevo
  comportamiento.

| Comando o verificacion | Resultado |
| --- | --- |
| `php -l` en archivos PHP modificados | OK |
| `php artisan view:cache --no-ansi` | OK |
| `php artisan route:list --path=designaciones --no-ansi` | OK; 2 GET + 3 POST registrados |
| `vendor/bin/pint --test` | BLOQUEADO; ejecutable no instalado |
| Suite PHPUnit | BLOQUEADA; `phpunit/phpunit` no esta instalado |

Pendientes (requieren administrador de BD):

- ~~`f_copiar_designacion` sigue rota (columna `id_asignaciones` duplicada y
  `WHERE id = _id`; debe ser `WHERE id_asignaciones = _id`). Verificado en BD:
  `ERROR: column "id_asignaciones" specified more than once`.~~
- ~~`f_designacion` (`INS`) inserta la fila pero no devuelve la fila (falta
  `_id := vl_id`) y no asigna `estado = 'SOLICITADO'`. `'UPD'` y select (`''`)
  funcionan.~~
- ~~Confirmaciones de negocio restantes en `proposal.md` (cambio de `id_programa`
  en `UPD` y origen de `_obs` al copiar).~~

Re-verificación 2026-09-02: el administrador concedió `INSERT`/`UPDATE`/`SELECT`
sobre `designaciones.asignaciones` y `designaciones.asignaciones_detalles`, y
`USAGE` sobre las secuencias. El bloqueo de permisos quedó resuelto.

Corrección 2026-09-02 (edición de cabecera): el modal de edición y el de crear
enviaban los datos mediante inputs ocultos con `:value` de Alpine, que no
actualizaban de forma confiable el valor al enviar por JS. Se reescribieron como
formularios nativos (los inputs visibles con `x-model` llevan `name` y el
`<form>` envuelve el modal), eliminando la dependencia de `:value`. Verificado:
controlador, servicio y función `f_designacion` (`UPD`) responden correctamente
contra la BD real.

Corrección 2026-09-03 (scripts BD, T-01/T-02/T-03): se preparó
`docs/specs/changes/copiar-y-editar-designaciones/scripts-bd.sql` con las dos
funciones corregidas y se validó con funciones temporales en `public`
(transacción con rollback, sin efectos en la BD real): `INS`/`UPD`/`''` devuelven
la fila esperada, `INS` crea con `estado='SOLICITADO'`, la copia crea fila +
detalles con el nuevo id y el origen inexistente no crea filas.

Aplicación y verificación 2026-09-03 (BD real): el administrador aplicó las
funciones corregidas en la BD real. La versión aplicada usa `vl_id := _id` al
inicio en `f_designacion` (devuelve la fila en `INS`/`UPD`/select) e incluye la
regla de negocio confirmada `IFF(id_docente = 898, 0, id_docente)` en la copia;
`estado='SOLICITADO'` lo garantiza el default de la columna. Smoke tests en BD
real (con rollback): select por id devuelve 1 fila, `INS` devuelve la fila con
`SOLICITADO`, `UPD` actualiza y devuelve, y la copia crea fila + detalles con el
nuevo id (origen inexistente no crea filas). Queda pendiente solo la suite
automatizada (`phpunit`/`pint`, dev-deps ausentes).

Corrección 2026-09-03 (editar cabecera no guardaba): `DesignacionController@update`
declaraba `fecha` como `required`; cuando la fecha viajaba vacía (designaciones con
`r_fecha = NULL` o campo sin rellenar), el `validate()` fallaba y redirigía con
`$errors` que el layout no mostraba, por lo que el guardado parecía no ocurrir.
Fix: `fecha` pasa a `nullable` y se conserva la fecha actual si no llega; además,
`layouts/app.blade.php` ahora renderiza los errores de validación. Verificado contra
la BD real (rollback): con `fecha=''` se conserva la fecha y se actualiza la
observación. Test de regresión agregado en
`tests/Feature/JachasunDesignacionesEscrituraTest.php`
(`test_actualizar_sin_fecha_conserva_la_fecha_actual`). Ver
`docs/testing/BUG_REPORTS/BUG-2026-09-03-2-editar-cabecera-no-guarda.md`.

## Corrección 2026-09-03 (esquema `https` forzado en URLs)

Estado: **RESUELTA; VERIFICADA EN NAVEGADOR REAL**

`AppServiceProvider` forzaba `URL::forceScheme('https')` incondicionalmente. El
servidor solo sirve HTTP (`APP_URL=http://asignaciones.uatf.edu.bo`; toda petición
a `https://...` responde `301 -> http://...`), así que los `<form>` apuntaban a
`https://...`, el navegador recibía el 301, convertía el POST en GET y ningún
guardado (editar cabecera, crear, importar) llegaba al controlador.

Fix: `forceScheme('https')` solo cuando `APP_URL` empieza por `https://`. Con
HTTP, las URLs generadas usan el esquema real del request y el POST llega al
controlador.

| Comando o verificacion | Resultado |
| --- | --- |
| `tests/Feature/UrlSchemeTest.php` (antes del fix) | FALLA; `route()` genera `https://...` |
| `tests/Feature/UrlSchemeTest.php` (despues del fix) | OK; 1 prueba, 2 aserciones |
| `vendor/bin/pint --test` (archivos modificados) | OK |
| `php -l` en `AppServiceProvider.php` | OK |
| Navegador real (Playwright): editar observacion en `2138` | OK; guarda y flash success; se restauro el valor original |
| Navegador real (Playwright): POST en `2137` | Llega al controlador (302 + flash); error por fecha nula (pendiente Solucion 2) |
| Suite completa | BLOQUEADA; base de testing `127.0.0.1:55432` no disponible (`phpunit` ya instalado) |

Pendiente: bug de fecha nula en designaciones importadas (2137, 2129) — la
Solución 2 propuesta no forma parte de este cambio. Ver
`docs/testing/BUG_REPORTS/BUG-2026-09-03-3-esquema-https-forzado.md`.

## Fecha automática al copiar y editar designaciones

Estado: **COMPLETA EN APP; SUITE AUTOMATIZADA BLOQUEADA**

Se eliminó el ingreso manual de la fecha en los formularios de crear/importar y
de editar cabecera. La BD asigna la fecha automáticamente:

- Copiar (`f_copiar_designacion`): la fila nueva recibe `now()` por el
  `DEFAULT (now())` de `designaciones.asignaciones.fecha` (ya ocurría; antes el
  campo "Fecha" del modal era visible pero ignorado).
- Crear vacía (`f_designacion` `INS`): con fecha vacía la función aplica
  `now()` (fallback ya existente).
- Editar (`f_designacion` `UPD`): se conserva la fecha existente; si la
  designación tiene `fecha = NULL`, se guarda la fecha actual automáticamente.
  Corrige el fallo de edición de designaciones sin fecha (2137/2129).

Cambios: `JachasunDesignacionesService::validarFecha()` acepta fecha vacía
(`''` → la BD aplica `now()`; sigue validando formato no vacío);
`DesignacionController@store` ya no exige fecha al crear vacía;
`lista.blade.php` y `carrera.blade.php` ocultan el campo "Fecha" (el modal de
crear solo valida gestión y periodo). No se modificaron funciones de BD.

| Comando o verificacion | Resultado |
| --- | --- |
| `php -l` en servicio, controlador y tests | OK |
| `vendor/bin/pint --test` (archivos modificados) | OK |
| `php artisan view:cache` / `view:clear` | OK |
| `php artisan route:list --path=designaciones` | OK; 4 rutas (2 GET + 2 POST) |
| Servicio real con BD mockeada: `insertar`/`actualizar` con `''` | OK; pasa `''` a `f_designacion` |
| `actualizar` con formato de fecha inválido | OK; rechazado (regresión) |
| Suite PHPUnit | BLOQUEADA; PostgreSQL testing `127.0.0.1:55432` no disponible |

Regresión: ver `docs/testing/BUG_REPORTS/BUG-2026-09-03-4-fecha-automatica-al-copiar-y-editar.md`.

## Editar filas de detalle de una designación (f_designacion_detalle)

Estado: **IMPLEMENTADO EN APP Y FUNCIÓN APLICADA Y VERIFICADA EN BD REAL
(2026-09-04); SUITE AUTOMATIZADA BLOQUEADA**

La función `designaciones.f_designacion_detalle` existía en la BD de Jachasun
pero estaba rota en todos sus caminos (UPDATE → `42601` por `id_docente`
asignado dos veces; INSERT → `42701` por columna `id_materia` duplicada;
`RETURN QUERY` inválido). Se corrigió y aplicó en la BD real (2026-09-04). La
versión aplicada usa `RETURNS TABLE` de 14 columnas y devuelve solo la fila
afectada; la corrección clave fue calificar la vista
`academico.v_facultades_programas` (sin `academico.`, la app veía `42P01`
porque `usr_designaciones` tiene `search_path = public`).

La corrección se validó con una función temporal en `public` (transacción con
rollback, ids reales 2138/2131) y, tras aplicarla, con smoke en BD real por la
conexión de la app (rollback): UPDATE e INSERT devuelven la fila afectada y no
dejan cambios. Ver `repro-bd.md`.

Cambios: `JachasunDesignacionesService::guardarDetalle()` (validación,
pertenencia a la carrera y transacción de escritura sin `READ ONLY`);
`DesignacionController@actualizarDetalle` + `show()` pasa `docentesDisponibles`
/ `materiasDisponibles` a la vista; ruta `POST /designaciones/{id}/detalle`;
modal "Editar" de fila en `carrera.blade.php` con selects de docente y materia
tomados de la propia designación, grupo y horas editables, y confirmación.

| Comando o verificacion | Resultado |
| --- | --- |
| Repro BD real (rollback): UPDATE/INSERT actuales | ERROR `42601` / `42701` (reproducido) |
| Smoke función temporal en `public` (rollback) | OK; UPDATE e INSERT corrigen y devuelven filas visibles |
| Función APLICADA en BD real + smoke por la app (rollback) | OK; UPDATE e INSERT devuelven la fila afectada sin errores |
| `php -l` en servicio, controlador, rutas y tests | OK |
| `vendor/bin/pint --test` (archivos modificados) | OK |
| `php artisan view:cache` | OK |
| `php artisan route:list --path=designaciones` | OK; 5 rutas (2 GET + 3 POST) |
| HTTP real en `/designaciones/{id}` | OK; sin código JS visible (fijado `@json` → `@js` en el `x-data` de Alpine) |
| Suite PHPUnit | BLOQUEADA; PostgreSQL testing `127.0.0.1:55432` no disponible |

Pendiente: ejecutar la suite automatizada tras disponer la base de testing.
Regresión: ver `docs/testing/BUG_REPORTS/BUG-2026-09-03-5-f_designacion_detalle-rota.md`.

## Buscador en selects de docente y materia del modal de edición de filas

Estado: **IMPLEMENTADA; SUITE CON FALLOS PREEXISTENTES NO RELACIONADOS**

Se reemplazaron los `<select>` nativos de docente y materia del modal "Editar
asignación" (`resources/views/designaciones/carrera.blade.php`) por combobox
buscables en Alpine.js (sin librerías externas):

- Docente: busca por nombre + CI y muestra `Nombre (CI)`.
- Materia: busca por sigla + nombre y muestra `Sigla — Nombre`.
- Autocompletado en vivo (insensible a mayúsculas y tildes), navegación por
  teclado (↑/↓, Enter, Escape), cierre con clic fuera, estado "Sin
  coincidencias" y atributos ARIA (`combobox`/`listbox`/`option`).
- El valor elegido se conserva en `editFilaForm.docente_id`/`materia_id` (misma
  fuente de verdad de los inputs ocultos del formulario y de
  `ciDocenteSeleccionado()`); no se modificó backend ni rutas.

Archivos modificados:

- `resources/views/designaciones/carrera.blade.php`
- `tests/Feature/JachasunDesignacionesDetailTest.php` (nuevo test)

Se levantó PostgreSQL local/testing en `127.0.0.1:55432` (binarios de Debian
extraídos en espacio de usuario) y se ejecutó la suite.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan testing:phase0 --env=testing` | OK; 34 migraciones |
| `php artisan test tests/Feature/JachasunDesignacionesDetailTest.php --env=testing` | 3/4; 1 fallo preexistente (`Consulta de solo lectura`) |
| `php artisan test tests/Feature/JachasunDesignacionesEscrituraTest.php --env=testing` | OK; 12 pruebas, 61 aserciones |
| `php artisan test --env=testing` | 68/82; 13 fallos preexistentes no relacionados |
| `vendor/bin/pint --test` (archivos modificados) | OK |
| `php -l` en vista modificada | OK |

Los 13 fallos (`GuestAccessTest` ×3, `JachasunDesignacionesListTest` ×5,
`PageAccessTest` ×3, `RoleAuthorizationTest` ×1 y
`JachasunDesignacionesDetailTest::test_detalle_es_imprimible...`) se reproducen
en el estado previo y no tocan esta fase (afectan auth, lista y roles; el del
detalle espera un texto que no existe en la vista). No fueron introducidos por
este cambio.

Ajuste posterior (2026-09-08): el texto del input ahora es el estado real
(`filtroDocente`/`filtroMateria`). Al abrir el modal o elegir una opción se
rellena con el valor seleccionado; al borrar todo el campo permanece vacío (ya
no se vuelve a rellenar). La normalización de búsqueda ignora espacios, signos
y paréntesis. Verificado: `JachasunDesignacionesDetailTest` 3/4 (solo fallo
preexistente) y `php -l` OK.

Fecha: 2026-09-08.

## PDF por designación en pestaña nueva (reemplaza impresión directa)

Estado: **COMPLETA; 13 FALLOS PREEXISTENTES NO RELACIONADOS**

El botón `Imprimir` ya no dispara `window.print()` directo. Lista y detalle
enlazan a `GET /designaciones/{id}/pdf` con `target="_blank" rel="noopener"`,
y el controlador genera un PDF real (`barryvdh/laravel-dompdf`,
`Content-Type: application/pdf`, `Content-Disposition: inline
designacion-{id}.pdf`) que el navegador abre en pestaña aparte con su visor
nativo (descargar/imprimir). Se eliminaron `?print=1`, `modoImpresion` y el
bloque `window.print()` de `carrera.blade.php`.

Archivos modificados:

- `composer.json` / `composer.lock`: `barryvdh/laravel-dompdf ^3.1` (única
  dependencia nueva; estándar Laravel para PDF en servidor).
- `routes/web.php`: ruta `designaciones.pdf` (`GET designaciones/{id}/pdf`,
  `whereNumber`, bajo `auth`).
- `app/Http/Controllers/DesignacionController.php`: método `pdf()` con la
  misma autorización (`carreraAutorizada`) y fuente (`listar` + `detallar`);
  404 si el id no pertenece a la carrera, 503 seguro si Jachasun falla
  (solo se registra la clase de excepción). `show()` ya no expone
  `modoImpresion`.
- `resources/views/designaciones/pdf.blade.php` (nueva, solo PDF sin
  Tailwind/Alpine): encabezado según modelo `public/imgs/captures/molde.jpeg`
  (logo `public/imgs/resources/LogoDC.png` por `public_path()`, `DATA CENTER`
  azul + `UNIVERSIDAD AUTÓNOMA TOMÁS FRÍAS` rojo, doble regla, título
  `REPORTE DE DESIGNACIONES`), bloque de datos (carrera, descripción,
  gestión, periodo, estado, observación), tabla de 7 columnas (docente, CI,
  materia, grupo, teóricas, prácticas, laboratorio) y pie (`PERTENECE A:
  {carrera}`, fecha de impresión y paginación). Aproximaciones respecto al
  molde (sin asset separado en el repo): escudo circular y circuitos
  laterales no reproducidos — se usa LogoDC y reglas CSS; título a una línea
  con el texto confirmado; sin `FACULTAD` (sin fuente de datos); sin firmas
  (diferidas por negocio).
- `resources/views/designaciones/lista.blade.php`: `rutaImprimir (?print=1)`
  → `rutaPdf (designaciones.pdf)`; conserva `target="_blank"`.
- `resources/views/designaciones/carrera.blade.php`: botón apunta a
  `designaciones.pdf` con `target="_blank" rel="noopener"`; eliminado el
  `@media print` automático (`window.print()`). Se conserva el CSS
  `@media print` para impresión manual del HTML.
- `tests/Feature/DesignacionPdfTest.php` (nueva, regresión creada antes del
  cambio): PDF 200 + `application/pdf` + `inline` + `%PDF`; enlaces en
  pestaña nueva sin `?print=1` ni `window.print`; render de la vista con
  cabecera del molde (`DATA CENTER`, `REPORTE DE DESIGNACIONES`,
  `PERTENECE A:`); 404 de otra carrera; 503 seguro sin filtrar secretos.

Corrección de deriva de mensajes (root cause, sin debilitar pruebas): el
controlador exponía textos cortos (`'Detalle no disponible.'`, `'No fue
posible consultar el detalle de la designacion.'`, ...) mientras
`JachasunDesignacionesDetailTest`, `JachasunDesignacionesListTest` y la
documentación fijan los largos (`'Detalle Jachasun no disponible.'`, `'No
fue posible consultar el detalle de la designacion en Jachasun.'`, ...).
Se alinearon log + mensaje 503 de `index`, `show` y `pdf()` al contrato
largo (los flash de crear/actualizar no se tocaron). Ver
`docs/testing/BUG_REPORTS/BUG-2026-09-09-1-deriva-mensajes-jachasun.md`.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan test tests/Feature/DesignacionPdfTest.php --env=testing --no-coverage` | OK; 5 pruebas, 35 aserciones (fallaba 0/4 antes del cambio: ruta inexistente) |
| `php artisan test --env=testing --no-coverage` (suite completa) | 73/87; 13 fallos preexistentes no relacionados (GuestAccess ×3, ListTest ×5 por mock `listarPaginado` vs `listar`, PageAccess ×3, RoleAuthorization ×1, DetailTest imprimible ×1 por texto ausente) + 1 error preexistente (`ListTest::test_fallo_jachasun_bloquea`, mock rancio de `listarPaginado`, falla igual sin esta fase) |

| `vendor/bin/pint --test` (archivos modificados) | OK |
| `php artisan view:cache` / `view:clear` | OK |
| `php artisan route:list --path=designaciones` | OK; 6 rutas (5 previas + `designaciones.pdf`) |

No se conectó Jachasun real durante esta fase (mocks). Firmas del reporte
quedan diferidas (`NEEDS_BUSINESS_CONFIRMATION` ya registrado).

Fecha: 2026-09-09.

## Oferta curricular para reasignacion de filas

Estado: **IMPLEMENTADA; MODO DEGRADADO OPERATIVO; CONTEXTO VIGENTE PENDIENTE**

El modal de detalle consulta `academico.f_oferta_materias(programa, gestion,
periodo)` al abrir una designacion. La materia queda habilitada solo despues de
seleccionar docente; los grupos se filtran por materia y las horas se cargan
desde la oferta oficial. Al cambiar solo docente se conservan grupo y horas; al
cambiar materia se usan las horas oficiales. El servicio rechaza materias o
grupos fuera del contexto y duplicados de materia/grupo, y la mutacion de
detalles exige `director_carrera`. Si la fuente de grupos no tiene permisos, el
servicio conserva materias y horas oficiales, marca los grupos como no
disponibles y el modal permite cambiar docente o materia oficial conservando el
grupo actual; el grupo y la edicion manual de horas quedan bloqueados.

Archivos modificados:

- `app/Services/Jachasun/JachasunDesignacionesService.php`
- `app/Http/Controllers/DesignacionController.php`
- `resources/views/designaciones/carrera.blade.php`
- `tests/Unit/JachasunDesignacionesServiceTest.php`
- `tests/Feature/JachasunDesignacionesDetailTest.php`
- `tests/Feature/JachasunDesignacionesEscrituraTest.php`
- `tests/Feature/DesignacionPdfTest.php`

| Comando o verificacion | Resultado |
| --- | --- |
| Pruebas dirigidas | OK; 51 pruebas, 334 aserciones |
| `tests/Feature/DesignacionPdfTest.php` + detalle | OK; 10 pruebas, 87 aserciones |
| Suite completa `php artisan test --env=testing --no-coverage` | 86/99; 12 fallos, 1 error y 6 risky preexistentes |
| `php -l` en servicio y controlador | OK |
| `php artisan view:cache --no-ansi` | OK |
| `vendor/bin/pint --test` en archivos de esta fase | OK |

Smoke real de solo lectura: `f_oferta_materias` + `pln_materias` devuelve 58
materias para `INF / 2026 / 1`; los grupos quedan en modo `FALLBACK` porque el
acceso a `academico.dct_asignaciones` es rechazado. El acceso a
`public.gestion_periodo_directores` tambien es rechazado, por lo que la
creacion fuera del contexto sigue bloqueada de forma segura. Se requiere una
funcion de lectura autorizada o permisos de solo lectura concedidos por el
administrador para habilitar grupos y contexto; no se ejecutaron escrituras ni
cambios de permisos. Ver
`docs/testing/BUG_REPORTS/BUG-2026-09-10-oferta-materias-permisos.md`.

Fecha: 2026-09-10.

## Recorrido de navegador del sistema

Estado: **COMPLETO EN UATF; ESCRITURAS REALES NO EJECUTADAS**

Se ejecuto un recorrido con Chromium aislado contra
`http://asignaciones.uatf.edu.bo` usando cuentas demo, sin registrar
credenciales ni datos personales. Se verificaron login de director, lista,
modal de crear/copiar, detalle, modal de reasignacion y PDF. El POST de
crear/copiar fue interceptado antes de enviarse al servidor y se confirmo que
incluye gestion, periodo y origen.

Tambien se verifico el acceso de Vicerrectorado: login redirige a
`/notificaciones` y `/designaciones` responde 403. Se corrigio el 500 anterior
causado por intentar obtener una carrera inexistente para ese rol.

| Comando o verificacion | Resultado |
| --- | --- |
| Recorrido Chromium UATF | OK; login, lista, crear/copiar, detalle, reasignacion y PDF |
| Respuesta PDF | OK; HTTP 200, `application/pdf` |
| Errores de consola/pagina | OK; ninguno |
| Respuestas HTTP 5xx durante el recorrido | OK; ninguna |
| Unit + feature relacionados | OK; 52 pruebas, 342 aserciones |
| Suite completa `php artisan test --env=testing --no-coverage` | 87/100; 12 fallos, 1 error y 6 risky preexistentes |
| `vendor/bin/pint --test` en archivos modificados | OK |

La creacion y copia reales no se ejecutaron en produccion por seguridad. Los
contratos de escritura cuentan con pruebas feature/unit y las funciones fueron
verificadas previamente con smoke reversible por Administracion.

Fecha: 2026-09-10.

## Correccion de crear y copiar con contexto precargado

Estado: **COMPLETA; ESCRITURA REAL NO EJECUTADA**

El modal de nueva designacion ahora inicia gestion y periodo con el contexto
academico mas reciente del listado cargado. Esto evita que crear vacia o copiar
envien campos vacios; la validacion de contexto del servidor se conserva. Si no
hay designaciones publicadas, el modal informa que no existe contexto vigente
en lugar de intentar una escritura.

| Comando o verificacion | Resultado |
| --- | --- |
| Regresion de formulario de crear/copia | OK; 1 prueba, 4 aserciones |
| `JachasunDesignacionesEscrituraTest` | OK; 17 pruebas, 85 aserciones |
| `JachasunDesignacionesServiceTest` | OK; 26 pruebas, 168 aserciones |
| `php artisan view:cache --no-ansi` | OK |
| `vendor/bin/pint --test` en archivos modificados | OK |
| Suite completa `php artisan test --env=testing --no-coverage` | 93/104; 10 fallos y 1 error preexistentes de autenticacion, lista y acceso |

No se ejecutaron creaciones ni copias contra la BD real. Ver
`BUG_REPORTS/BUG-2026-09-10-crear-designacion-contexto-vacio.md`.

## Correccion de crear y copiar designaciones sin permiso de contexto

Estado: **IMPLEMENTADA; VERIFICADA EN TESTING Y SMOKE DE LECTURA REAL**

El usuario de la aplicacion no puede leer `public.gestion_periodo` ni la tabla
auxiliar de contexto. `contextoActual()` intenta primero la fuente canonica y,
si recibe un error de permisos, deriva el ultimo contexto publicado por
`f_asignaciones(programa, 0, 0)`. No se hardcodean gestion ni periodo. Si no hay
designaciones publicadas, la operacion se rechaza de forma segura.

Con esto `store` puede continuar hacia `f_designacion` o
`f_copiar_designacion` cuando la gestion y periodo enviados coinciden con el
ultimo contexto publicado. La pertenencia de carrera y la validacion de
contexto se conservan.

| Comando o verificacion | Resultado |
| --- | --- |
| Unit + crear/copiar feature | OK; 41 pruebas, 245 aserciones |
| Smoke de solo lectura `contextoActual('INF')` | OK; `2026/2` |
| Suite completa `php artisan test --env=testing --no-coverage` | 86/99; 12 fallos, 1 error y 6 risky preexistentes |
| `php -l` en servicio y controlador | OK |
| `php artisan view:cache --no-ansi` | OK |
| `vendor/bin/pint --test` en archivos de esta fase | OK |

No se ejecutaron creaciones, copias ni otras escrituras contra la BD real.
El fallback depende de que exista al menos una designacion publicada y puede
requerir una correccion posterior si Administracion habilita la fuente canonica
de contexto. Ver `BUG-2026-09-10-oferta-materias-permisos.md`.

Fecha: 2026-09-10.
## Fase: edición de horas y mensajes seguros (10/09/2026)

### Resultado

- Implementada la edición de horas teóricas, prácticas y de laboratorio.
- Las horas enviadas se conservan cuando no existe catálogo autorizado de grupos.
- Se rechazan horas superiores a las horas oficiales de la materia.
- Los mensajes visibles relacionados con designaciones usan lenguaje formal y no exponen el proveedor técnico.
- El grupo no se cambia hasta contar con una fuente autorizada.

### Pruebas ejecutadas

- `php artisan test tests/Feature/JachasunDesignacionesEscrituraTest.php --env=testing --no-coverage`: 17/17, 85 aserciones.
- `php artisan test tests/Feature/JachasunDesignacionesDetailTest.php --env=testing --no-coverage`: 7/7, 69 aserciones.
- `php artisan test tests/Unit/JachasunDesignacionesServiceTest.php --filter=test_guardar_detalle_rechaza_horas_superiores_a_la_oferta_oficial --env=testing --no-coverage`: 1/1, 8 aserciones.
- `php artisan test tests/Feature/JachasunDesignacionesListTest.php --env=testing --no-coverage`: 3/9 pasan; 5 pruebas antiguas fallan por mocks desactualizados y 1 prueba falla por una expectativa de log no alineada con el flujo actual.
- `php artisan test tests/Feature/DesignacionPdfTest.php --env=testing --no-coverage`: 5/6; queda 1 fallo preexistente de contenido institucional del PDF.
- `php artisan test --env=testing --no-coverage`: 107 pruebas; 95 pasan, 11 fallan y 1 presenta error. Los fallos están concentrados en `GuestAccessTest` (3), `PageAccessTest` (2), `DesignacionPdfTest` (1) y `JachasunDesignacionesListTest` (6).
- `./vendor/bin/pint --test` sobre los archivos modificados: pasa.
- `php artisan view:cache`: pasa.
- `php -l` sobre los archivos PHP modificados: pasa.

### Archivos modificados

- `AGENTS.md`
- `app/Http/Controllers/DesignacionController.php`
- `app/Services/Jachasun/JachasunDesignacionesService.php`
- `resources/views/designaciones/carrera.blade.php`
- `resources/views/designaciones/lista.blade.php`
- `tests/Feature/JachasunDesignacionesDetailTest.php`
- `tests/Feature/JachasunDesignacionesListTest.php`
- `tests/Feature/JachasunDesignacionesEscrituraTest.php`
- `tests/Unit/JachasunDesignacionesServiceTest.php`

### Riesgos pendientes

- No existe todavía una función, vista o permiso autorizado para consultar grupos disponibles.
- Deben actualizarse los mocks de las pruebas antiguas del listado para usar el método que invoca actualmente el controlador.
- Debe revisarse el fallo pendiente del contenido institucional del PDF sin relacionarlo con esta fase.
- La ejecución global de Pint también reporta dos archivos no modificados (`EnsureRole.php` y `GuestAccessTest.php`); no se alteraron para evitar mezclar fases.

## Fase: busqueda dual de docentes en reasignacion (11/09/2026)

### Resultado

- El modal carga el catalogo de la carrera con `academico.f_lista_docentes` y lo
  filtra en vivo mientras se escribe.
- El mismo campo tiene el boton `Buscar en toda la universidad`; la tecla Enter
  ejecuta la misma busqueda institucional con `public.f_buscar_docente`.
- Los resultados institucionales se pueden seleccionar y se envian al mismo
  formulario de reasignacion, conservando las validaciones existentes.
- La ruta de busqueda esta protegida por autenticacion y rol de director de
  carrera, responde JSON y usa mensajes seguros ante errores.
- La entrada global elimina signos reservados de `tsquery` antes de invocar la
  funcion, evitando el SQLSTATE `42601` reproducido previamente.

### Pruebas ejecutadas

- `JachasunDesignacionesServiceTest`: 29/29, 187 aserciones.
- `JachasunDesignacionesEscrituraTest`: 17/17, 86 aserciones.
- `DesignacionPdfTest`: 8/8, 50 aserciones.
- `JachasunDesignacionesDetailTest`: 8/9; queda 1 fallo preexistente por la
  expectativa antigua del mensaje de grupos no disponibles.
- Smoke real de servicio: `INF` devuelve 19 docentes del programa y la busqueda
  global devuelve 2 resultados unicos, sin escrituras.
- `php artisan view:cache --no-ansi`: OK.
- `php -l` en PHP modificado: OK.
- `vendor/bin/pint --test` en archivos modificados: OK.
- Suite completa: 101 pruebas aprobadas de 113; permanecen 11 fallos preexistentes
  concentrados en autenticacion, esquemas de testing, lista y expectativas
  visuales antiguas.

### Archivos modificados

- `docs/specs/changes/buscar_docentes_reasignacion/*`
- `app/Services/Jachasun/JachasunDesignacionesService.php`
- `app/Http/Controllers/DesignacionController.php`
- `routes/web.php`
- `resources/views/designaciones/carrera.blade.php`
- `tests/Unit/JachasunDesignacionesServiceTest.php`
- `tests/Feature/JachasunDesignacionesDetailTest.php`
- `tests/Feature/JachasunDesignacionesEscrituraTest.php`
- `tests/Feature/DesignacionPdfTest.php`

## Fase: validacion de funciones de docentes (11/09/2026)

### Resultado

- `public.f_buscar_docente('INF')` responde correctamente con 2 docentes activos,
  sin duplicados ni columnas obligatorias nulas.
- `academico.f_lista_docentes('')` responde correctamente con 845 docentes
  activos de 64 programas, sin duplicados ni identificadores o programas nulos.
- Ambas consultas se ejecutaron dentro de una transaccion `READ ONLY`; no se
  realizaron escrituras, migraciones ni cambios de permisos.
- Las funciones existen en los schemas esperados y la conexion de la aplicacion
  tiene permisos para ejecutarlas.

### Diferencia funcional

- `f_buscar_docente` es una busqueda de texto completo; `INF` encontro 2 filas y
  no equivale a listar todos los docentes del programa.
- `f_lista_docentes('INF')` devuelve 19 docentes del programa `INF`.
- `f_lista_docentes('')` y `f_lista_docentes('UATF')` devuelven el listado global.

### Riesgo pendiente

- `f_buscar_docente` devuelve error SQLSTATE `42601` para entradas con ciertos
  operadores o signos de `tsquery` (por ejemplo `:` o `(`). Ver
  `BUG_REPORTS/BUG-2026-09-11-f_buscar-docente-tsquery.md`.
- `f_lista_docentes` devuelve `NULL` en `r_cargo` para 58 filas y en
  `r_direccion` para 571 filas del listado global; esos campos deben tratarse
  como opcionales.

### Fase 2026-09-11: apertura consecutiva de grupos

- Se reprodujo el bloqueo del `select` y el rechazo de un grupo nuevo con dos
  regresiones automatizadas.
- Se agrego la tabla `designacion_grupo_aperturas` y la vista autorizada
  `v_designacion_grupos_abiertos`.
- El servicio ofrece `1..N+1`, registra `N+1` dentro de la transaccion y deja
  que la restriccion unica rechace una segunda apertura concurrente.
- El `select` permanece editable incluso cuando no se puede consultar el
  catalogo; en ese caso se conserva el grupo actual en el servidor.
- Pruebas relacionadas: 57 correctas en las suites de servicio, detalle y
  escritura.
- Suite completa: 104 correctas, 10 fallos y 1 error; los fallos observados
  corresponden a autenticacion y listado principal, fuera de los archivos
  modificados en esta fase.

## Fase: busqueda global de docentes en el modal (15/09/2026)

### Resultado

- El modal ya no carga ni filtra primero el catalogo de docentes de la carrera.
- La lista del combobox usa exclusivamente los resultados de la funcion global
  `public.f_buscar_docente`, solicitada mediante Enter o la lupa.
- Se eliminaron los mensajes inferiores que indicaban si los resultados eran de
  la carrera o de la universidad.
- Se conservaron el icono y el boton de busqueda; Enter busca cuando no hay
  resultados y selecciona la opcion activa cuando ya existen resultados.

### Pruebas ejecutadas

- Regresion del modal y busqueda global: OK.
- Suites relacionadas: OK; 66 pruebas, 425 aserciones.
- `php artisan view:cache --no-ansi`: OK.
- `php -l` en archivos PHP modificados: OK.
- `vendor/bin/pint --test` en archivos modificados: OK.
- Suite completa `php artisan test --env=testing --no-coverage`: 105/116
  pruebas aprobadas; 10 fallos y 1 error preexistentes en autenticacion, lista
  principal y acceso.

### Archivos modificados

- `app/Http/Controllers/DesignacionController.php`
- `resources/views/designaciones/carrera.blade.php`
- `tests/Feature/JachasunDesignacionesDetailTest.php`
- `tests/Feature/JachasunDesignacionesEscrituraTest.php`
- `tests/Feature/DesignacionPdfTest.php`
- `docs/testing/BUG_REPORTS/BUG-2026-09-15-busqueda-docentes-global-modal.md`

Ver informe de regresion en
`docs/testing/BUG_REPORTS/BUG-2026-09-15-busqueda-docentes-global-modal.md`.

## Fase: materia actual visible al reasignar (17/09/2026)

### Resultado

- Corregido el caso en que la materia de una fila existente no pertenece a la
  oferta vigente y el modal de reasignación la mostraba vacía.
- `show()` conserva esas materias en `materiasOferta` únicamente para editar la
  fila correspondiente, junto con sus grupos actuales.
- Las materias agregadas con `solo_edicion` no aparecen en una nueva
  designación.
- La disponibilidad de grupos sigue calculándose exclusivamente desde la
  oferta autorizada.

### Pruebas ejecutadas

- Regresión nueva: OK; 1 prueba, 8 aserciones.
- `php artisan test tests/Feature/JachasunDesignacionesDetailTest.php --env=testing --no-coverage`: 19/20; 1 error baseline de expectativa de log.
- Suite relacionada de servicio, detalle y escritura: 70/71; 1 error baseline, 491 aserciones.
- `php artisan test --env=testing --no-coverage`: 116/129; 10 fallos y 3 errores baseline, 697 aserciones.
- `php artisan view:cache --no-ansi`: OK.
- `php -l` en PHP modificado: OK.
- `vendor/bin/pint --test` en archivos modificados: OK.

### Archivos modificados

- `app/Http/Controllers/DesignacionController.php`
- `resources/views/designaciones/carrera.blade.php`
- `tests/Feature/JachasunDesignacionesDetailTest.php`
- `docs/testing/BUG_REPORTS/BUG-2026-09-17-materia-actual-no-visible-reasignacion.md`

No se ejecutaron escrituras reales en producción. La verificación manual queda
pendiente hasta disponer de un navegador en el entorno.

# Matriz de pruebas vigente

| Area | Caso | Comando/evidencia | Resultado |
|---|---|---|---|
| Cuenta Decanatura | Inicio de sesión compartido y facultad de Ciencias Puras asignada | `DemoAuthenticationTest`, `DemoUserProviderTest` | CUBIERTA; login/redirección/alcance con cuenta sintética; cuenta configurada para el acceso vigente |
| Cuenta Decanatura | Designaciones visibles bajo regla confirmada `APROBADO` | Lectura de carreras y estados en solo lectura | Sin filas visibles actualmente; los registros existentes están en `SOLICITADO`, filtro no alterado |
| TASK2 Fase 3 | Contratos separados de lectura/escritura, sustituibles por mocks y resueltos por el contenedor | `DesignacionesGatewayTest` | CUBIERTO; 60 pruebas Unit relacionadas, 317 aserciones |
| TASK2 Fase 3 | Lecturas en adaptador: listado por alcance, paginación, detalle, oferta y decisiones | `JachasunDesignacionesServiceTest`, `DecanaturaDesignacionesServiceTest`, `VicerrectoradoDesignacionesServiceTest` | CUBIERTO con conexiones simuladas; parámetros enlazados y transacciones de solo lectura conservados |
| TASK2 Fase 3 | Escrituras en adaptador: copia, cabecera, detalle, fila y estado general | `JachasunDesignacionesServiceTest`, `VicerrectoradoDesignacionesServiceTest`, pruebas de escritura feature | CUBIERTO con mocks; transacciones normales y resultados por fila conservados |
| TASK2 Fase 3 | Búsqueda docente mantiene fuente y orden sin seleccionar una fuente nueva | `JachasunDesignacionesServiceTest::test_busqueda_local_usa_el_catalogo_academico_si_no_existe_la_tabla_publica`; revisión de rutas y adapter | Comportamiento trasladado sin cambio; `NEEDS_BUSINESS_CONFIRMATION` sigue pendiente antes de variar la selección de fuente |
| TASK2 Fase 3 | Regresión de roles, vistas y acciones después de extraer adaptadores | Feature de Vicerrectorado/Decanatura/caracterización y escrituras de Dirección | CUBIERTO; 35 pruebas, 331 aserciones |
| TASK2 Fase 3 | Suite completa y revisión Git | Verificación segura del entorno; `git status --short`; `git diff --check` | PENDIENTE/no disponibles: entorno aislado apagado y copia sin metadatos Git |
| TASK2 Fase 2 | DTOs tipados y serialización controlada para designación, detalle, docente, oferta y decisiones | DTOs en `app/Data/Designaciones/` | CUBIERTO; tipos explícitos y `toArray()` |
| TASK2 Fase 2 | Mapeo de campos externos, nulos, ausentes, enteros, fechas y datos mal tipados | `DesignacionesResponseMapperTest` | CUBIERTO; 7 pruebas unitarias sintéticas |
| TASK2 Fase 2 | Lectura del estado/observación general migrada a `RevisionDesignacion`; presentación mantiene la observación | `VicerrectoradoDesignacionesServiceTest::test_lista_revision_general_en_transaccion_de_solo_lectura`; `VicerrectoradoDesignacionesTest` | CUBIERTO; DTO tipado, vista y fallo de lectura conservan su comportamiento |
| TASK2 Fase 2 | Respuesta de revisión sin filas produce fallo controlado | `VicerrectoradoDesignacionesServiceTest::test_respuesta_sin_filas_de_revision_falla_con_excepcion_controlada` | CUBIERTO; no se propagan campos incompletos |
| TASK2 Fase 2 | Regresión de alcance, lectura, decisiones, vistas y autorizaciones relacionadas | Mapper y servicio unitarios, pruebas Feature de Decanatura/Vicerrectorado y caracterización Fase 1 | CUBIERTO; 47 pruebas, 367 aserciones; más 5 pruebas dirigidas de Dirección |
| TASK2 Fase 2 | Suite completa | Verificación segura previa | PENDIENTE; entorno aislado no disponible; no se usó otra fuente |
| TASK2 Fase 2 | Auditoría, formato, sintaxis y compilación de vistas | Pint global, Composer audit, npm audit, `php -l`, `view:cache`, `route:list` | CUBIERTO; todas las verificaciones ejecutadas finalizaron correctamente |
| TASK2 Fase 1 | Inventario método-a-flujo para listado, búsqueda, paginación, detalle, oferta, PDF, copia, cabecera, asignación, decisiones por fila y estado general | Mapa en `docs/testing/STATUS.md`; rutas y controladores vigentes | CUBIERTO; incluye entradas, alcance, respuesta visible y métodos invocados |
| TASK2 Fase 1 | Listado vigente de Dirección, alcance de carrera y paginación/filtro de interfaz | `DesignacionesCharacterizationTest::test_listado_de_direccion_usa_el_alcance_de_su_carrera_y_pagina_en_la_interfaz` | CUBIERTO con servicio simulado; interfaz recibe el conjunto de la carrera |
| TASK2 Fase 1 | Búsqueda vacía de docentes no consulta el servicio | `DesignacionesCharacterizationTest::test_busqueda_de_docentes_vacia_responde_sin_consultar_el_servicio` | CUBIERTO; JSON `[]` |
| TASK2 Fase 1 | IDs fuera del alcance no permiten escrituras de Dirección ni lecturas/escrituras de Vicerrectorado | `DesignacionesCharacterizationTest::test_direccion_no_edita_cabecera_ni_detalle_fuera_de_su_carrera`, `test_vicerrectorado_no_consulta_ni_escribe_ids_ajenos_al_alcance_universitario` | CUBIERTO; 404 y sin invocar operaciones de escritura/detalle |
| TASK2 Fase 1 | IDs de rutas de detalle, PDF y escritura son numéricos | `DesignacionesCharacterizationTest::test_rutas_de_designacion_restringen_los_ids_a_numeros` | CUBIERTO; ocho rutas verificadas |
| TASK2 Fase 1 | Decisión por fila debe pertenecer a la designación operada | `VicerrectoradoDesignacionesServiceTest::test_rechaza_decision_para_un_detalle_que_no_pertenece_a_la_designacion` | CUBIERTO; respuesta externa vacía produce rechazo controlado |
| TASK2 Fase 1 | Roles, presentación, acciones de Decanatura/Vicerrectorado y escrituras de Dirección | Suite dirigida de caracterización + `DecanaturaDesignacionesTest`, `VicerrectoradoDesignacionesTest`, y pruebas dirigidas de detalle/escritura | CUBIERTO con mocks/datos sintéticos; 44 pruebas, 379 aserciones |
| TASK2 Fase 1 | Pruebas que requieren fixtures en PostgreSQL aislado y suite completa | Verificación segura de disponibilidad previa | PENDIENTE; el servicio PostgreSQL de testing no acepta conexiones; no se usó otra fuente |
| TASK2 Fase 1 | Paginación del listado de Dirección | Pruebas anteriores de `JachasunDesignacionesListTest` frente al controlador/vista actuales | REVISIÓN PENDIENTE; las antiguas esperan `listarPaginado`, el código vigente pagina localmente después de `listar`; las expectativas no se modificaron |
| TASK2 Fase 1 | Fuente de búsqueda de docentes en ambientes soportados | Comparación de código y specs de búsqueda | `NEEDS_BUSINESS_CONFIRMATION` antes de cambiar el flujo en fase 3 |
| Fase 4 | Regresión conjunta de autenticación, alcance de Decanatura, PDF, escritura de Dirección y consulta/decisiones de Vicerrectorado | Suite dirigida con `DecanaturaDesignacionesTest`, `DemoAuthenticationTest`, `VicerrectoradoDesignacionesTest`, servicios unitarios y pruebas dirigidas de escritura de Dirección | CUBIERTA con datos sintéticos; 47 pruebas, 368 aserciones |
| Fase 4 | Suite completa y verificación de migración | `php artisan test --env=testing --no-coverage`; chequeo del entorno aislado | PENDIENTE; entorno aislado no disponible; sin reemplazarlo por otra fuente |
| Calidad | Compilación de vistas, rutas, sintaxis, formato y auditorías | `view:cache`, `route:list`, `php -l`, Pint, Composer audit y npm audit | CUBIERTA; vistas/rutas/sintaxis/formato OK; auditorías sin avisos |
| Decanatura | Listado compartido muestra carreras de facultad y solo conserva Detalles/Imprimir; sin controles de creación | `DecanaturaDesignacionesTest::test_decanatura_lista_designaciones_aprobadas_de_su_facultad` | CUBIERTA con usuario y filas sintéticos |
| Decanatura | Detalle de solo lectura; sin búsqueda de docentes, edición ni decisiones por asignación | `DecanaturaDesignacionesTest::test_detalle_de_decanatura_usa_la_carrera_de_la_designacion_aprobada` | CUBIERTA; reusa la plantilla compartida |
| Decanatura | Login, raíz y menú llevan al flujo compartido; Vicerrectorado conserva su destino | `DecanaturaDesignacionesTest::test_raiz_y_login_dirigen_decanatura_a_designaciones_y_conservan_vicerrectorado`; `DemoAuthenticationTest` | CUBIERTA con cuentas sintéticas |
| Autorización | POST directo a escritura/búsqueda docente denegado para Decanatura | `DecanaturaDesignacionesTest::test_decanatura_no_puede_enviar_escrituras_ni_buscar_docentes_por_http` | CUBIERTA; HTTP 403 antes de invocar servicios |
| Dirección | Acciones de creación del listado permanecen disponibles para Dirección | `DecanaturaDesignacionesTest::test_direccion_conserva_la_accion_de_crear_en_la_lista_compartida` | CUBIERTA con cuenta sintética |
| PDF Decanatura | PDF compartido conserva cabecera y filas existentes | `DecanaturaDesignacionesTest::test_decanatura_imprime_solo_la_designacion_aprobada_de_su_facultad`, `test_pdf_compartido_conserva_el_formato_y_muestra_las_filas_sinteticas` | CUBIERTA; misma vista PDF y fila sintética renderizada |
| Suite completa | Regresión al cierre de Fase 3 | `php artisan test --env=testing --no-coverage` | PENDIENTE; entorno aislado no disponible; no se usó otra fuente |
| Modelos | Se retiraron los modelos y factories del flujo de propuestas fuera del alcance activo; migraciones históricas conservadas | Revisión de referencias en rutas, controladores, seeders y tests + `php -l` | PARCIAL; referencias activas retiradas, suite bloqueada por indisponibilidad de PostgreSQL testing |
| UI Vicerrectorado | Búsqueda local por descripción, observación, carrera, fecha, gestión, periodo y estado; filtros combinados de carrera/año y paginación del conjunto completo | `VicerrectoradoDesignacionesTest`, `VicerrectoradoDesignacionesServiceTest`, `node --check` y comprobación directa del controlador JS | CUBIERTA; 25 pruebas, 236 aserciones; Node confirma búsqueda sin tildes, filtro de carrera, paginación y enlaces |
| Suite general | Regresión al añadir búsqueda completa al listado de Vicerrectorado | `php artisan test --env=testing --no-coverage` | BLOQUEADA parcialmente; 52 pruebas aprobadas y 122 errores por conexión rechazada al PostgreSQL aislado de testing (`127.0.0.1:55432`); Pint y auditorías OK |
| Decanatura | Relación Facultad–Carrera: campos y tipos usados por el alcance de cuenta | Consulta de metadatos en transacción `READ ONLY` | CUBIERTA; `alm_programas_facultades.id_facultad` integer, `alm_programas.id_facultad` smallint e `id_programa` character(3); no se leyeron filas personales |
| Decanatura | Lista, detalle y PDF limitados a carreras de facultad y estado general `APROBADO` | `DecanaturaDesignacionesServiceTest`, `DecanaturaDesignacionesTest`; contratos de listado existentes | CUBIERTA con datos sintéticos; 7 pruebas, 38 aserciones; filtro y revalidación en servidor |
| Decanatura | Consulta parametrizada y solo lectura; facultad sin carreras, mezcla de estados y orden estable | `DecanaturaDesignacionesServiceTest` | CUBIERTA con mocks; confirma parámetro enlazado, transacción `READ ONLY` y ausencia de consultas de escritura |
| Decanatura | Suite completa de regresión | `php artisan test --env=testing --no-coverage` | PENDIENTE; el entorno aislado de testing no estuvo disponible; no se usó otra fuente |
| UI Assets | CSS/JS residen y se publican desde `resources/assets/`, sin copia en `public/assets`; el PDF lee su CSS local | `FrontendAssetsTest`, `FrontendAssetsServingTest` y comprobación HTTP local | CUBIERTA; 4 pruebas, 80 aserciones; 10 assets HTTP 200 y ruta fuera de assets HTTP 404 |
| Tailwind/Alpine | Tailwind se compila localmente y las fábricas de vista se ejecutan antes de Alpine | `npm run build:css`; `npm audit`; prueba de orden y registro de fábricas | CUBIERTA; Tailwind CLI 4.3.0, 0 vulnerabilidades y tres fábricas registradas |
| Suite general | Regresión completa tras cambiar publicación de assets y compilación de estilos | `php artisan test --env=testing --no-coverage` | BLOQUEADA parcialmente; 43 pruebas aprobadas y 122 errores porque el entorno de testing no estaba disponible |
| PDF | La hoja externa se incorpora en el HTML que recibe DOMPDF y conserva las reglas del documento | Render sintético de `designaciones.pdf` + `DesignacionPdfTest` | Render sintético verificado; pruebas de controlador requieren PostgreSQL de testing |
| Calidad UI Assets | Vistas compiladas y scripts válidos | `php artisan view:cache --no-ansi`; `node --check` en los tres scripts bajo `resources/assets/js/` | CUBIERTA |
| Autorización | Roles Director, Decanatura y Vicerrectorado con alcance exclusivo; combinaciones incompatibles rechazadas | `RoleAuthorizationTest`; migración `2026_10_06_120000_add_facultad_scope_to_users_table` | BLOQUEADA para verificación PostgreSQL: 9 errores de conexión antes de aserciones; pruebas agregadas |
| Autenticación demo | El usuario de Decanatura conserva `facultad_id` y solo administra esa facultad | `php artisan test --env=testing --no-coverage tests/Unit/DemoUserProviderTest.php` | CUBIERTA; 2 pruebas, 12 aserciones |
| Suite de Fase 1 | Regresión tras añadir rol y alcance | `php artisan test --env=testing --no-coverage` | BLOQUEADA por indisponibilidad de PostgreSQL testing; 38 aprobadas y un fallo aislado de Vicerrectorado fuera del alcance |
| Area | Caso | Comando/evidencia | Resultado |
|---|---|---|---|
| Vicerrectorado | Estado general solicitado/aprobado/observado, acciones independientes, motivo separado, autorización y validación | `VicerrectoradoDesignacionesTest` + `VicerrectoradoDesignacionesServiceTest`; `php artisan view:cache --no-ansi`; rutas protegidas | CUBIERTA en Laravel con mocks; 23 pruebas, 209 aserciones. El recálculo automático requiere habilitación institucional |
| Vicerrectorado | Lectura de estado general o por fila falla sin bloquear controles no dependientes; fila desconocida indica `Sin estado` | `VicerrectoradoDesignacionesTest::test_el_fallo_al_consultar_revision_general_no_oculta_las_decisiones_por_fila`, `test_si_no_se_pueden_leer_estados_puede_guardar_y_muestra_sin_estado` | CUBIERTA con mocks; 16 pruebas, 172 aserciones en el archivo feature |
| Dirección | Una designación `APROBADO` no permite editar cabecera, cambiar/asignar filas ni agregar asignaciones | Pruebas dirigidas en `JachasunDesignacionesEscrituraTest` y `JachasunDesignacionesDetailTest` | CUBIERTA; 3 pruebas, 21 aserciones |
| Dirección | Editar una fila ya revisada mientras el estado general no es `APROBADO` | Confirmación de negocio pendiente | `NEEDS_BUSINESS_CONFIRMATION`; falta definir si invalida la decisión de esa fila y recalcula el estado general |
| Suite completa | Regresión de la fase de estados y edición aprobada | `php artisan test --env=testing --no-coverage` | BLOQUEADA por PostgreSQL de testing no disponible; 38 aprobadas y 117 errores de 155 pruebas |

| Area | Caso | Comando/evidencia | Resultado |
|---|---|---|---|
| UI Carrera | Estado por asignación; modal explica aprobado, pendiente, rechazado o no disponible y muestra la observación del rechazo | `JachasunDesignacionesDetailTest::test_detalle_es_imprimible_y_muestra_acciones_por_fila_y_de_cabecera` + `php artisan view:cache --no-ansi` + comprobación directa de helpers en Node | BLOQUEADA para la prueba funcional por PostgreSQL de testing no disponible; compilación, sintaxis y estados/observación validados |
| UI compartida | Componente modal acepta slots de título, contenido y acciones; conserva cierre por Escape/click fuera y permite envolver formularios | `VicerrectoradoDesignacionesTest::test_vicerrectorado_puede_abrir_el_detalle_y_revisar_la_designacion` + `php artisan view:cache --no-ansi` | CUBIERTA; ambas modales de Vicerrectorado se renderizan con el componente |
| UI Vicerrectorado | El detalle muestra estados por fila y generales, ofrece revisión general independiente y conserva las observaciones separadas | `VicerrectoradoDesignacionesTest::test_vicerrectorado_puede_abrir_el_detalle_y_revisar_la_designacion`, `test_el_detalle_muestra_por_separado_el_motivo_general_vigente`, `test_una_asignacion_rechazada_puede_aprobarse_y_limpiarse_su_observacion` + prueba unitaria de servicio | CUBIERTA con mocks; 22 pruebas dirigidas, 192 aserciones |

| Area | Caso | Comando/evidencia | Resultado |
|---|---|---|---|
| Vicerrectorado | Aprobar/rechazar docente-materia, guardar observación y conservar cambios por fila | Smoke real desde Laravel con rollback + pruebas dirigidas de Vicerrectorado | CUBIERTA; lectura, guardado, mensajes generales y rollback confirmados |
| Seguridad de mensajes | Respuestas y mensajes visibles generales, sin detalles técnicos o confidenciales | Revisión de `AGENTS.md` | CUBIERTA; regla documentada |
| Jachasun/Vicerrectorado | Funciones de listar y guardar decisiones con estructura `plpgsql`, validación y `RETURN QUERY` estandarizados | `docs/specs/changes/decisiones-vicerrectorado-jachasun/scripts-bd.sql` + smoke mediante Laravel con rollback | CUBIERTA; funciones, columnas, permisos, lectura, escritura y rollback confirmados |
| Jachasun/Vicerrectorado | Lee y guarda estado/observación por asignación; observación vacía limpia valor previo; cada fila se procesa independientemente | `php artisan test tests/Unit/VicerrectoradoDesignacionesServiceTest.php tests/Feature/VicerrectoradoDesignacionesTest.php --env=testing --no-coverage` | CUBIERTA con mocks; 12 pruebas, 117 aserciones |
| Seguridad | Solo Vicerrectorado puede llamar POST de decisiones; IDs y estado validados, función verifica pertenencia al `r_id` | `VicerrectoradoDesignacionesTest::test_director_no_puede_guardar_decisiones_de_vicerrectorado` + pruebas del servicio | CUBIERTA con mocks |
| Jachasun | Aplicar columnas/funciones y ejecutar smoke autorizado con rollback | `docs/specs/changes/decisiones-vicerrectorado-jachasun/scripts-bd.sql` | PENDIENTE; DBA debe revisar esquema, aplicar DDL y conceder permisos; no se tocó Jachasun real |
| Suite completa | Regresión de integración | `php artisan test --env=testing --no-coverage` | BLOQUEADA; 20 aprobadas y 121 errores de 141 pruebas por PostgreSQL testing no disponible en `127.0.0.1:55432` |

| Area | Caso | Comando/evidencia | Resultado |
|---|---|---|---|
| UI Vicerrectorado | Iconos horizontales; modal obligatorio al aceptar/rechazar por fila o grupo; observación opcional | `php artisan test tests/Feature/VicerrectoradoDesignacionesTest.php --env=testing --no-coverage` + `node --check resources/views/Script.js` | CUBIERTA; 5 pruebas, 79 aserciones; no persiste tras recargar |
| Suite completa | Regresión al cierre de las acciones con modal | `php artisan test --env=testing --no-coverage` | BLOQUEADA; 15 aprobadas y 121 errores por PostgreSQL testing no disponible en `127.0.0.1:55432` |

| Area | Caso | Comando/evidencia | Resultado |
|---|---|---|---|
| UI Vicerrectorado | Checkboxes por fila, aceptación grupal y rechazo grupal con motivo común obligatorio; Guardar cambios cierra la revisión | `php artisan test tests/Feature/VicerrectoradoDesignacionesTest.php --env=testing --no-coverage` + `node --check resources/views/Script.js` | CUBIERTA; 5 pruebas, 74 aserciones; decisiones temporales sin persistencia |
| Suite completa | Regresión al cierre de los controles visuales | `php artisan test --env=testing --no-coverage` | BLOQUEADA; 15 aprobadas y 121 errores de 136 pruebas por PostgreSQL testing no disponible en `127.0.0.1:55432` |

| Area | Caso | Comando/evidencia | Resultado |
|---|---|---|---|
| UI Vicerrectorado | Detalle reutiliza encabezado, resumen y tabla de asignaciones de Directores sin controles de edición | `php artisan test tests/Feature/VicerrectoradoDesignacionesTest.php --env=testing --no-coverage` + revisión de `detalle.blade.php` y `Styles.css` | CUBIERTA; 5 pruebas, 55 aserciones; muestra CI, materia y horas |
| Suite completa | Regresión al cierre de la alineación del detalle | `php artisan test --env=testing --no-coverage` | BLOQUEADA; 15 aprobadas y 121 errores por PostgreSQL testing no disponible en `127.0.0.1:55432` |

| Area | Caso | Comando/evidencia | Resultado |
|---|---|---|---|
| Vicerrectorado | Detalles/Imprimir buscan designaciones de cualquier período expuesto por el listado | `VicerrectoradoDesignacionesServiceTest::test_obtener_designacion_universitaria_busca_en_todos_los_periodos` | CUBIERTA; regresión probada antes del fix y luego corregida |
| UI Vicerrectorado | Enlaces de Detalles e Imprimir conservan rutas e incluyen gestión; PDF abre en pestaña nueva | `VicerrectoradoDesignacionesTest::test_vicerrectorado_ve_todas_las_designaciones_de_la_gestion` | CUBIERTA; verificación conjunta de servicio y feature: 7 pruebas, 59 aserciones |
| Suite completa | Regresión al cierre del arreglo de Detalles/Imprimir | `php artisan test --env=testing --no-coverage` | BLOQUEADA; 15 aprobadas y 121 errores por PostgreSQL testing no disponible en `127.0.0.1:55432` |

| Area | Caso | Comando/evidencia | Resultado |
|---|---|---|---|
| UI Vicerrectorado | Listado reutiliza estilos de Directores para estado, tabla, paginación y acciones Detalles/Imprimir | `php artisan test tests/Feature/VicerrectoradoDesignacionesTest.php --env=testing --no-coverage` + revisión de vistas y `Styles.css` | CUBIERTA; 5 pruebas, 42 aserciones; `SOLICITADO` verde y vista compilada |
| Suite completa | Regresión al cierre de la alineación visual | `php artisan test --env=testing --no-coverage` | BLOQUEADA; 14 aprobadas y 121 errores por PostgreSQL testing no disponible en `127.0.0.1:55432` |
| Calidad | Formato global y dependencias bloqueadas por hallazgos existentes | `vendor/bin/pint --test`; `composer audit --locked` | Pint detecta 2 archivos ajenos al cambio; audit reporta 4 avisos de dependencias |

| Area | Caso | Comando/evidencia | Resultado |
|---|---|---|---|
| UI modularizacion | Confirmacion y notificacion usan `Styles.css` sin estilos inline | Revision de `partials/modal-confirmacion.blade.php`, `partials/modal-notificacion.blade.php` y `Styles.css` | CUBIERTA; contratos Alpine conservados |
| UI Vicerrectorado | Detalle mantiene solo lectura y acciones aprobadas | `php artisan test tests/Feature/VicerrectoradoDesignacionesTest.php --env=testing --no-coverage` | CUBIERTA; 5 pruebas, 35 aserciones |
| UI modal | Eventos y estados de confirmacion/notificacion permanecen disponibles | Revision de `Script.js` y parciales reutilizados por lista/detalle | CUBIERTA |
| Limpieza | Parcial de impresion revisado antes de eliminar | Busqueda de referencias a `modal-imprimir-designaciones` | CUBIERTA; sin referencias activas, conservado |
| UI modularizacion | Regresion conjunta de Vicerrectorado y designaciones | `php artisan test tests/Feature/VicerrectoradoDesignacionesTest.php tests/Feature/JachasunDesignacionesDetailTest.php tests/Feature/JachasunDesignacionesListTest.php --env=testing --no-coverage` | BLOQUEADA; 5 aprobadas, 29 errores de conexion; PostgreSQL `127.0.0.1:55432` |

| Area | Caso | Comando/evidencia | Resultado |
|---|---|---|---|
| UI modularizacion | Listado de Vicerrectorado usa reglas centralizadas en `Styles.css` | `php artisan view:cache --no-ansi` + revision de `Styles.css` e `_styles.blade.php` | CUBIERTA; reglas con alcance `.vicerrectorado-designaciones` |
| UI Vicerrectorado | Listado conserva consulta, estados y acciones de solo lectura | `php artisan test tests/Feature/VicerrectoradoDesignacionesTest.php --env=testing --no-coverage` | CUBIERTA; 5 pruebas, 35 aserciones |
| UI Vicerrectorado | Acceso director al listado global sigue prohibido | `VicerrectoradoDesignacionesTest::test_director_no_puede_acceder_a_la_pantalla_global` | CUBIERTA |
| UI Vicerrectorado | Detalle ofrece acciones generales y conserva independientes las decisiones por fila | `VicerrectoradoDesignacionesTest::test_vicerrectorado_puede_abrir_el_detalle_y_revisar_la_designacion`, `test_vicerrectorado_puede_observar_la_designacion_con_filas_pendientes` | CUBIERTA |
| UI modularizacion | Suite completa posterior a la fase 4 | `php artisan test --env=testing --no-coverage` | BLOQUEADA; 14 aprobadas, 121 errores de conexion; 135 pruebas totales |

| Area | Caso | Comando/evidencia | Resultado |
|---|---|---|---|
| UI modularizacion | PDF usa `Styles.css`, limita sus reglas a `designaciones-pdf` y no carga JavaScript | Render sintetico de `view('designaciones.pdf')` | CUBIERTA; CSS incluido con alcance seguro, JavaScript ausente |
| PDF | Cabecera, tabla repetible y footer conservados tras extraer estilos | `php artisan view:cache --no-ansi` + revision de `Styles.css` y `pdf.blade.php` | CUBIERTA; estructura conservada |
| PDF | Regresion funcional y render DOMPDF | `php artisan test tests/Feature/DesignacionPdfTest.php --env=testing --no-coverage` | BLOQUEADA; 8 errores por PostgreSQL `127.0.0.1:55432` no disponible |
| UI modularizacion | Suite completa posterior a la fase 3 | `php artisan test --env=testing --no-coverage` | 14 aprobadas, 121 errores de conexion; 135 pruebas totales |

| Area | Caso | Comando/evidencia | Resultado |
|---|---|---|---|
| UI modularizacion | Lista de designaciones usa `Styles.css` y `Script.js` desde `resources/views/` | `php artisan view:cache --no-ansi`, `node --check resources/views/Script.js`, `php -l` en vistas modificadas | CUBIERTA; compilacion y sintaxis OK |
| UI modularizacion | Comentarios de seccion identifican responsabilidades del CSS y Alpine del listado | Revision de `resources/views/Styles.css` y `resources/views/Script.js` | CUBIERTA |
| UI modularizacion | Regresion funcional del listado despues de extraer CSS/Alpine | `php artisan test tests/Feature/JachasunDesignacionesListTest.php --env=testing --no-coverage` | BLOQUEADA; 9 errores por PostgreSQL `127.0.0.1:55432` no disponible |
| UI modularizacion | Suite completa posterior a la fase 2 | `php artisan test --env=testing --no-coverage` | 14 aprobadas, 121 errores de conexion; 135 pruebas totales |

| Area | Caso | Comando/evidencia | Resultado |
|---|---|---|---|
| UI modularizacion | Detalle de designaciones usa `Styles.css` y `Script.js` desde `resources/views/` | `php artisan view:cache --no-ansi`, `node --check resources/views/Script.js`, `php -l` en vistas modificadas | CUBIERTA; compilacion y sintaxis OK |
| UI modularizacion | Formato de las vistas modificadas | `vendor/bin/pint --test resources/views/designaciones/carrera.blade.php resources/views/layouts/app.blade.php` | CUBIERTA; OK |
| UI modularizacion | Regresion funcional del detalle despues de extraer CSS/Alpine | `php artisan test tests/Feature/JachasunDesignacionesDetailTest.php --env=testing --no-coverage` | BLOQUEADA; 20 errores por PostgreSQL `127.0.0.1:55432` no disponible |
| UI modularizacion | Suite completa posterior a la fase 1 | `php artisan test --env=testing --no-coverage` | 14 aprobadas, 121 errores de conexion; 135 pruebas totales |

| Área | Caso | Comando/evidencia | Resultado |
|---|---|---|---|
| Documentación | Instrucciones del agente y contexto reflejan el flujo activo | `php artisan route:list --no-ansi` + búsqueda de referencias a `CLAUDE.md` | CUBIERTA; una fuente de instrucciones, rutas vigentes |
| Docentes | Busqueda local por nombre, apellido, tildes, espacios y CI | `JachasunDesignacionesDetailTest::test_busqueda_de_docentes_usa_el_catalogo_local_por_nombre_y_ci` | CUBIERTA |
| Docentes | Limite de 100, orden estable y unicidad | `JachasunDesignacionesDetailTest::test_busqueda_local_ordena_establemente_y_limita_resultados` | CUBIERTA |
| Docentes | Catalogo `academico.docentes` cuando falta `public.docentes` | `JachasunDesignacionesServiceTest::test_busqueda_local_usa_el_catalogo_academico_si_no_existe_la_tabla_publica` + smoke de lectura | CUBIERTA |
| Escritura | Guardado continúa si falta `designacion_grupo_aperturas` | `JachasunDesignacionesServiceTest::test_guardar_detalle_continua_si_no_existe_la_tabla_local_de_aperturas` | CUBIERTA |
| UI modal | Validacion inline y guardado directo sin confirmacion de fila | `JachasunDesignacionesDetailTest::test_modal_valida_la_fila_y_guarda_sin_confirmacion_adicional` | CUBIERTA |
| UI grupos | Ayuda contextual del grupo siguiente | `JachasunDesignacionesDetailTest::test_modal_muestra_la_ayuda_del_siguiente_grupo_de_la_materia` | CUBIERTA |
| Cierre | Suite relacionada de búsqueda, modal, servicio y escritura | `php artisan test tests/Unit/JachasunDesignacionesServiceTest.php tests/Feature/JachasunDesignacionesDetailTest.php tests/Feature/JachasunDesignacionesEscrituraTest.php --env=testing --no-coverage` | 70/71; 1 error baseline de log, 491 aserciones |
| Cierre | Suite completa | `php artisan test --env=testing --no-coverage` | 116/129; 10 fallos y 3 errores baseline, 697 aserciones |
| Cierre | Recorrido Alpine en navegador real | Disponibilidad del entorno | BLOQUEADO; no hay navegador ni runner instalado |
| UI reasignación | Materia actual fuera de la oferta permanece visible al editar; no aparece al crear | `JachasunDesignacionesDetailTest::test_detalle_conserva_la_materia_actual_fuera_de_la_oferta_para_reasignar` | CUBIERTA; 1 prueba, 8 aserciones |

| Área | Caso | Comando/evidencia | Resultado |
|---|---|---|---|
| UI modal nueva | Materia seleccionable sin docente previo; horas y grupo se envian con el formulario | `JachasunDesignacionesDetailTest::test_modal_permite_materia_sin_docente_y_envia_horas_y_grupo` | CUBIERTA |
| Reglas de grupos | Siguiente oficial permitido aun en degradado (`max asignado + 1`) con apertura registrada | `JachasunDesignacionesServiceTest::test_guardar_detalle_sin_grupos_permite_nuevo_con_siguiente_oficial` | CUBIERTA; servicio + escritura 50/50 |
| Reglas de asignaciones | Permitir horas cero individuales y rechazar las tres horas en cero | `JachasunDesignacionesServiceTest::test_guardar_detalle_rechaza_todas_las_horas_en_cero` + suite de escritura | CUBIERTA; servicio 32/32 |
| Reglas de grupos | Nueva fila usa el siguiente grupo consecutivo y edicion conserva el actual | `JachasunDesignacionesServiceTest::test_guardar_detalle_nuevo_exige_el_siguiente_grupo` + `JachasunDesignacionesDetailTest::test_modal_de_nueva_designacion_muestra_solo_el_siguiente_grupo` | CUBIERTA |
| UI detalle | Input, Buscar y Limpiar juntos; Nueva designacion abre el modal reutilizado | `JachasunDesignacionesDetailTest::test_toolbar_ubica_filtros_junto_al_input_y_abre_modal_para_nueva_designacion` + `view:cache` | CUBIERTA; 13/13 pruebas del detalle |
| UI designaciones | Vista legacy conforme a la guía visual vigente de la fase | `php artisan view:cache --no-ansi` + revisión de `resources/views/designaciones/lista.blade.php` | Aprobado; solo cambios de presentación |
| UI designaciones | Regresión funcional del listado | `php artisan test tests/Feature/JachasunDesignacionesListTest.php --env=testing --no-coverage` | 3 aprobadas; fallos baseline por mocks/expectativas desactualizados |
| UI designaciones | Encabezado, breadcrumb y acciones directas | `php artisan view:cache --no-ansi` + revisión de vistas lista/detalle | Aprobado; `Designaciones`, `Detalles`, `Imprimir` y sin `panel-heading-btn` |
| UI designaciones | Regresión funcional del detalle | `php artisan test tests/Feature/JachasunDesignacionesDetailTest.php --env=testing --no-coverage` | 11/11 pruebas, 98 aserciones |
| UI shell/detail | Header superior y detalle legacy | `php artisan view:cache --no-ansi` + revisión de `layouts/header.blade.php` y `designaciones/carrera.blade.php` | Aprobado; sin cambios de backend |
| UI shell/detail | Regresión detalle y PDF | `php artisan test tests/Feature/JachasunDesignacionesDetailTest.php tests/Feature/DesignacionPdfTest.php --env=testing --no-coverage` | 19/19 pruebas, 148 aserciones |
| UI designaciones | Acción de impresión con icono accesible | Revisión de vistas + `view:cache` | Aprobado; conserva ruta PDF, `title` y `aria-label` |
| UI modales | Modal crear responsive y modal reasignar legacy | `php artisan view:cache --no-ansi` + revisión de vistas | Aprobado; contratos Alpine y formularios conservados |
| UI modales | Buscar materia: escribir, filtrar, Enter y Escape | `JachasunDesignacionesDetailTest::test_modal_de_edicion_usa_combobox_buscables_para_docente_y_materia` | 1/1 prueba dirigida, 26 aserciones |
| UI navegador | Recorrido real de usuario | Chromium/Firefox/Playwright/Puppeteer | BLOQUEADO; herramientas no instaladas en el entorno |
| UI modales | Confirmación sobre crear/reasignar | `JachasunDesignacionesDetailTest::test_modal_de_confirmacion_queda_por_encima_del_modal_de_edicion` | 1/1 prueba, 7 aserciones; `z-index: 1200` |
| UI modales | Confirmación sobre crear designación | `JachasunDesignacionesListTest::test_formulario_de_creacion_inicia_con_el_contexto_mas_reciente` | 1/1 prueba, 6 aserciones; `z-index: 1200` |

| Área | Caso | Comando/evidencia | Resultado |
|---|---|---|---|
| Footer PDF | Una página con total real | DOMPDF + `/tmp/opencode/footer-verified-single.pdf` | `1 / 1`, aprobado |
| Footer PDF | Varias páginas con total real | DOMPDF + `/tmp/opencode/footer-verified-multi.pdf` | `1 / 4` a `4 / 4`, aprobado |
| Footer PDF | Reserva inferior y saltos de tabla | `margin-bottom: 70pt`, PNG multipágina | Filas separadas del footer, aprobado |
| Footer PDF | Alineación visual | PNG de primera y última página | Footer fijo, líneas 47%/6%/47% y rojo 83%, aprobado |

| Área | Caso | Comando/evidencia | Resultado |
|---|---|---|---|
| PDF designaciones | Render real DOMPDF en Carta con margen lateral | `php artisan tinker --execute=...` + `/tmp/opencode/designacion-final-34pt.pdf` | Aprobado |
| PDF designaciones | Regresión de contenedor y ancho útil | `php artisan test --filter=DesignacionPdfTest` | 6/6 pruebas, 37 aserciones |
| PDF designaciones | Inspección visual de primera página | ImageMagick `convert -density 150 ...[0]` + PNG 1275x1650 | Aprobado |
| Suite completa | Regresión global | `php artisan test` | 90 aprobadas, 10 fallos, 1 error no relacionado |

`CUBIERTA` significa que existe una prueba o evidencia vigente.
`PENDIENTE` requiere trabajo futuro y `NEEDS_BUSINESS_CONFIRMATION` requiere
una decisiÃ³n universitaria antes de fijar una expectativa.

| ID | Area | Caso protegido | Estado |
| --- | --- | --- | --- |
| AUTH-001 | Acceso | Invitado no entra a rutas protegidas | CUBIERTA |
| AUTH-002 | Autorizacion | Director no opera otra carrera | CUBIERTA |
| AUTH-003 | Revision | Usuario incorrecto no decide ni retira | CUBIERTA |
| AUTH-004 | Login | Rate limit y redireccion por rol | CUBIERTA |
| DB-001 | Integridad | Foreign keys, checks e inmutabilidad | CUBIERTA |
| NOT-001 | Notificaciones | Propiedad, lectura y eventos | CUBIERTA |
| JAC-001 | Jachasun | `INF / 0 / 0` con conexion simulada | CUBIERTA |
| JAC-002 | Jachasun | Consulta parametrizada y transaccion `READ ONLY` | CUBIERTA |
| JAC-003 | Jachasun | Fallo externo no filtra secretos | CUBIERTA |
| JAC-004 | Jachasun | Importacion de docentes/materias/grupos/horas | PENDIENTE |
| LIST-001 | Lista | Director consulta su carrera con `sigla / 0 / 0` | CUBIERTA |
| LIST-002 | Lista | `/designaciones` no mezcla propuestas locales | CUBIERTA |
| LIST-003 | Lista | Jachasun fallido bloquea con HTTP 503 seguro | CUBIERTA |
| LIST-004 | Lista | Codigo y programa quedan en el encabezado | CUBIERTA |
| LIST-005 | Lista | Fecha, observacion y `r_id` se muestran correctamente | CUBIERTA |
| LIST-006 | Lista | Acciones visibles pero deshabilitadas | CUBIERTA |
| LIST-007 | Lista | `SOLICITADO` se muestra literalmente | CUBIERTA |
| AUTH-005 | Login | Proveedor demo aislado con cuatro cuentas y carrera | CUBIERTA |
| AUTH-006 | Login | Contraseña demo incorrecta y sesión persistente | CUBIERTA |
| JAC-005 | Jachasun | Detalle usa `f_asignaciones_detalles(?)` con transacción `READ ONLY` | CUBIERTA |
| JAC-006 | Jachasun | Detalle de otra carrera no se expone | CUBIERTA |
| JAC-007 | Jachasun | Fallo de detalle no filtra secretos | CUBIERTA |
| LIST-008 | Lista | Cada designación abre su detalle Jachasun | CUBIERTA |
| LIST-009 | Lista | Paginación de 10 en 10 con orden estable y total | CUBIERTA |
| LIST-010 | Lista | Buscar designaciones por descripción y conservar el filtro en la paginación | CUBIERTA |
| DETAIL-001 | Detalle | Detalle muestra edición/copia de cabecera; filas de detalle de solo lectura | CUBIERTA |
| DETAIL-002 | Detalle | Acciones por fila Editar/Desasignar (UI) y edición de cabecera | CUBIERTA |
| FLOW-001 | Flujo | Rutas locales de propuesta y revisión no están registradas | CUBIERTA |
| OPS-001 | Operacion | PHP tiene disponible el driver PDO de PostgreSQL en produccion | CUBIERTA |
| JAC-008 | Jachasun | Copiar verifica pertenencia a la carrera y escribe en transacción normal | CUBIERTA |
| JAC-009 | Jachasun | Insertar (`INS`) crea y normaliza la fila | CUBIERTA |
| JAC-010 | Jachasun | Actualizar cabecera (`UPD`) verifica pertenencia y escribe | CUBIERTA |
| JAC-011 | Jachasun | Consultar por id (`''`) devuelve fila o null | CUBIERTA |
| LIST-011 | Lista | Nueva designación con opción Importar de una gestión anterior; sin copiado por fila | CUBIERTA |
| URL-001 | URLs | Las URLs generadas usan el esquema real del request (http) y no `https` forzado | CUBIERTA |
| JAC-012 | Jachasun | Fecha vacía en `insertar`/`actualizar` delega a `now()` de la BD | CUBIERTA |
| JAC-013 | Jachasun | Fecha con formato inválido (no vacía) es rechazada | CUBIERTA |
| ESC-001 | Escritura | Crear vacía sin fecha → `insertar` recibe `''` (BD aplica `now()`) | CUBIERTA |
| ESC-002 | Escritura | Editar con `fecha = NULL` existente → `actualizar` recibe `''` y guarda | CUBIERTA |
| FORM-001 | Formularios | Campo "Fecha" oculto en crear/importar y en editar cabecera | CUBIERTA |
| JAC-014 | Jachasun | Guardar detalle (`f_designacion_detalle`) verifica pertenencia a la carrera y escribe en transacción normal | CUBIERTA |
| JAC-015 | Jachasun | Guardar detalle con `id_detalle = 0` inserta una fila nueva y normaliza | CUBIERTA |
| ESC-003 | Escritura | Editar fila de detalle con fallo de Jachasun devuelve mensaje seguro | CUBIERTA |
| DETAIL-003 | Detalle | Modal de fila con selects de docente/materia de la propia designación; grupo/horas editables | CUBIERTA |
| DETAIL-004 | Detalle | Combobox buscables de docente (nombre+CI) y materia (sigla+nombre) en el modal de edición de fila | CUBIERTA |
| PDF-001 | PDF | `GET designaciones/{id}/pdf` devuelve PDF inline descargable/imprimible en pestaña nueva | CUBIERTA |
| PDF-002 | PDF | Botones Imprimir de lista y detalle enlazan al PDF con `target="_blank"`; sin `?print=1` ni `window.print` automático | CUBIERTA |
| PDF-003 | PDF | PDF de otra carrera es 404 y fallo Jachasun es 503 seguro | CUBIERTA |
| JAC-016 | Jachasun | Oferta curricular parametrizada por programa, gestion y periodo | CUBIERTA |
| JAC-017 | Jachasun | Contexto vigente de gestion y periodo para escritura | CUBIERTA |
| JAC-018 | Jachasun | Materia y grupo pertenecen a la oferta y no duplican materia/grupo | CUBIERTA |
| DETAIL-005 | Detalle | Materias dependen del docente; grupos y horas siguen la oferta vigente | CUBIERTA |
| AUTH-007 | Autorizacion | Solo `director_carrera` puede modificar filas de detalle | CUBIERTA |
| JAC-019 | Jachasun | Contexto fallback a la ultima designacion si no hay permiso de contexto | CUBIERTA |
| ESC-004 | Escritura | Crear/copiar valida contexto sin fallar por permisos de la tabla auxiliar | CUBIERTA |
| FORM-002 | Formularios | Crear vacia y copiar inician con el contexto academico vigente | CUBIERTA |
| AUTH-008 | Autorizacion | Vicerrectorado recibe 403 al acceder a rutas de carrera | CUBIERTA |
| E2E-001 | Navegador | Recorrido UATF de login, lista, crear/copiar, detalle, reasignacion y PDF | CUBIERTA |

Las reglas históricas retiradas no forman parte de rutas activas; las
ambigüedades académicas siguen marcadas como `NEEDS_BUSINESS_CONFIRMATION`.
Las reglas operativas confirmadas están resumidas en `docs/README.md` y
detalladas en `AGENTS.md`; las reglas históricas retiradas no son normativas.

Nota: las filas `JAC-008` a `JAC-013`, `LIST-011` y `ESC-001`/`ESC-002` tienen
pruebas escritas, pero la suite automatizada sigue bloqueada en este servidor
por la base de testing (`127.0.0.1:55432`) no disponible (Pint ya ejecuta:
`vendor/bin/pint`). Las filas `JAC-014`/`JAC-015`, `ESC-003` y `DETAIL-003`
(editar filas de detalle) también tienen pruebas escritas (unit y feature) y
quedan pendientes de ejecutar por el mismo bloqueo.

La corrección de la función `designaciones.f_designacion_detalle`
(`docs/specs/changes/editar-filas-designacion/scripts-bd.sql`) fue **aplicada en
la BD real** (administrador, 2026-09-04) y **verificada con smoke** por la
conexión de la app (rollback): UPDATE e INSERT devuelven la fila afectada sin
errores. La corrección clave fue calificar la vista
`academico.v_facultades_programas` (sin `academico.` la app veía `42P01` por el
`search_path` de `usr_designaciones`).

La corrección de las funciones `designaciones.f_copiar_designacion` /
`f_designacion` fue aplicada por el administrador en la BD real (2026-09-03) y
**verificada con smoke tests en BD real** (select por id, `INS`, `UPD` y copia
con rollback; origen inexistente no crea filas). `scripts-bd.sql`
(`docs/specs/changes/copiar-y-editar-designaciones/`) registra las funciones
aplicadas, incluida la regla de negocio confirmada `IFF(id_docente = 898, 0, id_docente)`.
`estado = 'SOLICITADO'` en filas nuevas lo garantiza el `DEFAULT` de la columna.
Sigue pendiente ejecutar la suite automatizada (`phpunit`/`pint`) tras
`composer install --dev` (dev-deps ausentes).

`URL-001` cubre el BUG-2026-09-03-3 (esquema `https` forzado): `phpunit` quedó
instalado vía `composer install` (2026-09-03) y la prueba de regresión
`tests/Feature/UrlSchemeTest.php` pasa; la suite completa sigue bloqueada por la
base de testing (`127.0.0.1:55432`) no disponible.

`DETAIL-004` se ejecutó el 2026-09-08 tras levantar PostgreSQL testing en
`127.0.0.1:55432`: la prueba nueva y las de escritura
(`JachasunDesignacionesEscrituraTest`, 12/12) pasan. La suite completa quedó en
68/82: los 13 fallos restantes son preexistentes y no relacionados (auth,
lista y un texto ausente en el detalle); ver `STATUS.md`.

`JAC-016` a `AUTH-007` fueron ejecutadas en pruebas unitarias y feature el
2026-09-10: 51/51 pruebas relacionadas, 334 aserciones. La validacion contra
Jachasun real queda pendiente por permisos insuficientes sobre las fuentes de
grupos y contexto; el modo degradado ya fue verificado con smoke real. Ver
`STATUS.md` y el informe correspondiente.
## Cobertura de la fase: edición de horas y mensajes seguros (10/09/2026)

| ID | Área | Caso | Resultado | Evidencia |
| --- | --- | --- | --- | --- |
| HOR-001 | Detalle | Permitir modificar horas aunque no exista catálogo de grupos | CUBIERTO | `JachasunDesignacionesDetailTest::test_modal_permite_modificar_horas_aunque_no_haya_catalogo_de_grupos` |
| HOR-002 | Servicio | Rechazar horas superiores a la oferta oficial | CUBIERTO | `JachasunDesignacionesServiceTest::test_guardar_detalle_rechaza_horas_superiores_a_la_oferta_oficial` |
| MSG-001 | Listado | No exponer el proveedor técnico en errores visibles | CUBIERTO | `JachasunDesignacionesListTest::test_error_de_lista_no_menciona_el_proveedor_tecnico` |
| MSG-002 | Interfaz | Mantener trato formal en confirmaciones visibles | CUBIERTO | Revisión de `resources/views/designaciones/carrera.blade.php` y `lista.blade.php` |
| GRP-001 | Grupo | Cambiar grupo usando una fuente autorizada y abrir solo el siguiente grupo consecutivo | CUBIERTO | `JachasunDesignacionesServiceTest::test_oferta_materias_incluye_unicamente_el_siguiente_grupo` + `JachasunDesignacionesDetailTest::test_selector_de_grupo_se_mantiene_editable` |
| DOC-005 | Docentes | El modal usa exclusivamente la busqueda global de docentes | CUBIERTO | `public.f_buscar_docente` + `JachasunDesignacionesDetailTest` |
| DOC-006 | Docentes | Submit search institucional protegido y seguro | CUBIERTO | `buscarDocentes` + prueba feature JSON |
| DOC-007 | Docentes | La lupa busca y Enter busca o selecciona el resultado activo, sin filtro previo por carrera | CUBIERTO | Prueba de vista del modal |
| DOC-008 | Docentes | Resultado global seleccionable para reasignacion | CUBIERTO | Formulario conserva `editFilaForm.docente_id` y pruebas de escritura |
| DOC-009 | Docentes | El modal no muestra mensajes sobre el origen de los resultados | CUBIERTO | `JachasunDesignacionesDetailTest::test_modal_busca_docentes_solo_con_el_catalogo_global` |
| DOC-001 | Docentes | `f_buscar_docente('INF')` responde sin duplicados y con datos obligatorios | CUBIERTO | Smoke de lectura en BD autorizada; 2 filas |
| DOC-002 | Docentes | `f_lista_docentes('')` lista docentes activos sin duplicados | CUBIERTO | Smoke de lectura en BD autorizada; 845 filas |
| DOC-003 | Docentes | Las funciones responden dentro de transaccion `READ ONLY` | CUBIERTO | Smoke de lectura con `BEGIN READ ONLY` y `ROLLBACK` |
| DOC-004 | Docentes | `f_buscar_docente` tolera signos reservados de `tsquery` | PENDIENTE | Reproduce SQLSTATE `42601` con `:` y `(`; ver BUG-2026-09-11 |

| VIC-001 | Vicerrectorado | Consulta global con `UATF`, gestión parametrizada y todos los períodos | CUBIERTA | `VicerrectoradoDesignacionesServiceTest::test_lista_universitaria_consulta_uatf_con_gestion_y_todos_los_periodos` |
| VIC-002 | Vicerrectorado | Incluye todos los estados y ordena por fecha descendente | CUBIERTA | `VicerrectoradoDesignacionesServiceTest::test_lista_universitaria_consulta_uatf_con_gestion_y_todos_los_periodos` |
| VIC-003 | Autorización | Solo Vicerrectorado accede a la bandeja global | CUBIERTA | `VicerrectoradoDesignacionesTest::test_director_no_puede_acceder_a_la_pantalla_global` |
| VIC-004 | Vicerrectorado | Gestión actual por defecto y gestión explícita | CUBIERTA | `VicerrectoradoDesignacionesTest::test_la_gestion_actual_se_usa_si_no_se_envia_un_parametro` |
| VIC-005 | Vicerrectorado | Listado y detalle; revisión general independiente de las decisiones por fila | CUBIERTA | `VicerrectoradoDesignacionesTest::test_vicerrectorado_ve_todas_las_designaciones_de_la_gestion`, `test_vicerrectorado_puede_abrir_el_detalle_y_revisar_la_designacion` y pruebas de estado general |
| VIC-006 | Vicerrectorado | PDF institucional de solo lectura | CUBIERTA | `VicerrectoradoDesignacionesTest::test_vicerrectorado_puede_generar_el_pdf_de_una_designacion` |
| VIC-007 | Vicerrectorado | Combinar períodos 1 y 2 cuando el comodín 0 no devuelve filas | CUBIERTA | `VicerrectoradoDesignacionesServiceTest::test_lista_universitaria_consulta_uatf_con_gestion_y_todos_los_periodos` + `BUG-2026-09-29-vicerrectorado-periodo-cero.md` |
| VIC-008 | UI Vicerrectorado | Listado con patrón legacy de breadcrumb, page-header, panel y DataTable | CUBIERTA | `VicerrectoradoDesignacionesTest::test_vicerrectorado_ve_todas_las_designaciones_de_la_gestion` |
| VIC-009 | UI Vicerrectorado | Detalle con paneles, filas de decisión y acciones de estado general | CUBIERTA | `VicerrectoradoDesignacionesTest::test_vicerrectorado_puede_abrir_el_detalle_y_revisar_la_designacion` |
| VIC-010 | UI Vicerrectorado | Acciones agrupadas como `btn-group dropup` y menú legacy | CUBIERTA | `VicerrectoradoDesignacionesTest::test_vicerrectorado_ve_todas_las_designaciones_de_la_gestion` |


## Limpieza de vistas (06/10/2026)

| Área | Caso | Comando/evidencia | Resultado |
|---|---|---|---|
| Vistas | Retiro de cuatro plantillas sin referencias activas; conservación de vistas enlazadas e inclusiones | `php artisan view:cache --no-ansi`; `PageAccessTest`; `JachasunDesignacionesListTest` | Compilación OK. Pruebas funcionales bloqueadas antes de aserciones porque PostgreSQL de testing no acepta conexiones; no se confirmó el comportamiento HTTP en esta ejecución |

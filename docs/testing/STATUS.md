# Estado de pruebas

## Fase documental: limpieza con lógica protegida

Estado: **COMPLETA**

Alcance: reorganización de documentación, creación de guías canónicas y
archivo de material histórico. No se modificaron controladores, servicios,
Policies, modelos, migraciones, rutas, vistas ni configuración de ejecución.

Archivos canónicos:

- [`../ARCHITECTURE.md`](../ARCHITECTURE.md)
- [`../BUSINESS_RULES.md`](../BUSINESS_RULES.md)
- [`../OPERATIONS.md`](../OPERATIONS.md)
- [`../TESTING.md`](../TESTING.md)
- [`../INTEGRATION_JACHASUN.md`](../INTEGRATION_JACHASUN.md)

El historial se conserva en [`../archive/`](../archive/). Este archivo,
`TEST_MATRIX.md` y `BUG_REPORTS/` permanecen como rutas obligatorias de
trazabilidad.

## Reglas de cierre

- La suite se ejecuta contra PostgreSQL local/testing y datos sintéticos.
- Jachasun no se consulta durante la validación documental.
- Los estados, permisos e invariantes se comparan con código, migraciones y
  pruebas; ninguna ambigüedad se convierte en regla.
- Cada cambio de lógica futuro requiere una prueba de regresión antes de
  corregirse.

## Evidencia de esta fase

| Comando | Resultado |
| --- | --- |
| `composer test -- --env=testing` | OK; 83 pruebas, 464 aserciones |
| `vendor/bin/pint --test` | OK |
| `git diff --check` | OK |
| Verificación de enlaces y rutas archivadas | OK |
| Verificación de alcance documental de la fase | OK |

Fecha de cierre: 2026-08-10.

## Fase de conexión visible de `f_asignaciones`

Estado: **COMPLETA**

Se añadió la pantalla protegida de consulta institucional y se mantuvo el
endpoint JSON existente. La pantalla usa el mismo servicio de lectura, no
escribe en Jachasun ni en la base local y no altera la importación histórica.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan test tests/Feature/InstitutionalDesignacionesScreenTest.php tests/Feature/InstitutionalDesignacionesEndpointTest.php --env=testing --no-coverage` | OK; 11 pruebas, 54 aserciones |
| `composer test -- --env=testing` | OK; 91 pruebas, 507 aserciones |
| `vendor/bin/pint --test` | OK |
| `git diff --check` | OK |

No se validó una conexión real: permanece pendiente usar un ambiente de
desarrollo/staging autorizado con credenciales suministradas por administración.

## Ajuste de filtros institucionales

Se habilitó la combinación de gestión y periodo `0`. La pantalla y el endpoint
la envían sin transformación, permitiendo consultar todo el historial del
programa; la importación local no fue modificada.

| Comando o prueba | Resultado |
| --- | --- |
| Pruebas institucionales con `INF / 0 / 0` | OK; 2 casos cubiertos |
| `composer test -- --env=testing` | OK; 93 pruebas, 515 aserciones |
| `vendor/bin/pint --test` | OK |
| `git diff --check` | OK |

## Fase historica de lista institucional para directores (superada)

Estado: **COMPLETA**

En la implementacion historica, `INSTITUTIONAL_LIST_MODE=true` hacia que `/designaciones`
la sigla de la carrera del director autenticado con gestión y periodo `0`/`0`.
La vista es de solo lectura y no mezcla propuestas locales ni muestra acciones.
Si la integración está deshabilitada o falla, responde HTTP 503 con un mensaje
seguro. Con la bandera en `false` se conserva la lista local y sus rutas.

| Comando o prueba | Resultado |
| --- | --- |
| `php artisan test tests/Feature/InstitutionalDesignacionesListModeTest.php --env=testing --no-coverage` | OK; 4 pruebas, 30 aserciones |
| `composer test -- --env=testing` | OK; 97 pruebas, 545 aserciones |
| `vendor/bin/pint --test` | OK |
| `git diff --check` | OK |

La validación contra Jachasun real queda pendiente para el ambiente de
desarrollo/staging autorizado por administración; la suite local usa mocks.

## Riesgos pendientes

- Las reglas marcadas `NEEDS_BUSINESS_CONFIRMATION` requieren decisión
  universitaria antes de ampliar el flujo.
- La integración institucional continúa deshabilitada por defecto y no puede
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

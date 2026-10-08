# Instrucciones del proyecto

Este es el único archivo de instrucciones para agentes. Para el contexto breve,
consulte [`docs/README.md`](docs/README.md). El código, las rutas y las pruebas
vigentes prevalecen sobre specs, planes y bitácoras históricas.

## Proyecto y flujo

- Aplicación Laravel 13 / PHP 8.3+, PostgreSQL, Blade, Alpine.js, Tailwind CSS
  con CLI y DOMPDF; no usa React, Inertia ni Vite.
- Los CSS/JS propios residen en `resources/assets/` y se sirven por
  `/resources/assets/{path}` mediante `FrontendAssetController`; no se copian a
  `public/`. `resources/assets/css/tailwind.input.css` genera
  `resources/assets/css/tailwind.generated.css` con `npm run build:css`.
- En `resources/views/layouts/app.blade.php`, `@stack('scripts')` debe quedar
  antes del script de Alpine para registrar las fábricas `window.*` antes de
  evaluar `x-data`. No reintroduzca el CDN de Tailwind.
- Jachasun es la fuente institucional de designaciones y datos académicos.
  La integración está descrita en `docs/INTEGRATION_JACHASUN.md`.
- Dirección de Carrera trabaja en `/designaciones` y puede crear/copiar
  designaciones, editar cabeceras y asignaciones, buscar docentes e imprimir.
- Decanatura consulta e imprime desde `/designaciones` solo designaciones
  generales `APROBADO` de las carreras de su facultad. El detalle es de solo
  lectura; no puede crear, copiar, editar, buscar docentes ni tomar decisiones.
- Vicerrectorado consulta `/vicerrectorado/designaciones` y puede guardar
  decisiones `APROBADA`/`RECHAZADA` por asignación docente en
  `POST /vicerrectorado/designaciones/{id}/decisiones`, y decisiones generales
  `APROBADO`/`OBSERVADA` en
  `POST /vicerrectorado/designaciones/{id}/estado`. El estado general inicia en
  `SOLICITADO`; se calcula cuando todas las filas tienen decisión y requiere al
  menos una fila. Solo use las funciones autorizadas de Jachasun; la aplicación
  de `scripts-bd.sql` y permisos del DBA son requisito para habilitar
  persistencia en un ambiente. No reintroduzca rutas antiguas de revisión o
  propuestas.
- Determine rol y alcance en el servidor. Un director solo modifica datos de
  su carrera y no puede modificar una designación con estado general
  `APROBADO`; Decanatura solo consulta e imprime designaciones aprobadas de su
  facultad; Vicerrectorado solo modifica la decisión de asignaciones que
  pertenecen a la designación universitaria indicada. Limite IDs de rutas con
  `whereNumber`.

## Seguridad y datos

- No invente reglas universitarias. Ante ambigüedad, deténgase, pregunte y
  registre `NEEDS_BUSINESS_CONFIRMATION`.
- No exponga datos personales, identificadores internos innecesarios,
  credenciales ni detalles de infraestructura. Los errores visibles deben ser
  genéricos y seguros.
- Los mensajes visibles, respuestas al usuario y respuestas de la interfaz deben
  ser generales y breves. No mencione proveedores institucionales, bases de
  datos, esquemas, tablas, funciones, SQL, permisos, hosts, conexiones ni
  excepciones técnicas. Use mensajes como `Operación confirmada.` o `No fue
  posible completar la operación.`; los detalles técnicos solo pueden quedar en
  registros internos seguros y nunca deben devolverse al usuario.
- No use datos personales reales ni servicios externos reales en pruebas.
  Producción solo se opera con autorización; acciones destructivas se limitan
  a local/testing autorizado.
- No guarde secretos en archivos versionados. Testing usa PostgreSQL aislado;
  no use SQLite ni Jachasun real.

## Reglas de asignación

- Horas teóricas, prácticas y de laboratorio: enteros no negativos, cada valor
  no mayor que las horas oficiales; no guarde una fila con las tres en cero.
- Obtenga grupos solo de una fuente autorizada. Una nueva fila usa el siguiente
  grupo consecutivo según oferta, aperturas y filas existentes; si la fuente no
  está disponible, use `máximo asignado + 1`. Al editar, conserve el grupo
  actual. No invente ni liste grupos no autorizados.
- La materia puede elegirse antes que el docente.

## Cambios y verificación

- Antes de cambiar código, lea estas instrucciones, `docs/README.md`, el spec y
  las pruebas aplicables. Confirme el comportamiento en rutas/código, no en
  documentación histórica.
- Para un bug, primero añada una regresión que lo reproduzca. Ejecute pruebas
  relacionadas tras cada cambio y la suite completa al cerrar una fase. No
  quite ni debilite pruebas para obtener un resultado verde.
- Use políticas/middleware para autorizar; mantenga PostgreSQL y siga PSR-12.
  Ejecute `npm run build:css` cuando cambien clases Tailwind, junto con
  `npm audit`, `vendor/bin/pint --test` y `composer audit --locked` al cerrar
  cambios.
- Al cerrar una fase, actualice `docs/testing/STATUS.md` y
  `docs/testing/TEST_MATRIX.md` con archivos, comandos, resultados y riesgos.
  Cada bug requiere informe en `docs/testing/BUG_REPORTS/` y prueba de regresión.
- Deténgase al cerrar cada fase. No cree bitácoras nuevas; use el estado de
  testing como registro del trabajo.

## Higiene del repositorio

Revise `git status --short`, `git diff --check` y `docs/HIGIENE_REPOSITORIO.md`.
No agregue al repositorio credenciales, tareas temporales, bitácoras, salidas
regenerables ni configuraciones locales de asistentes. Este `AGENTS.md` es la
única fuente de instrucciones del agente.

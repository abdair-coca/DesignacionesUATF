# Contexto del proyecto

Guía corta para ubicarse en el sistema. El código y `AGENTS.md` prevalecen si
esta síntesis queda desactualizada.

## Qué es

Aplicación web Laravel para que Direcciones de Carrera gestionen designaciones
docentes de su carrera, Decanatura consulte e imprima las aprobadas de su
facultad, y Vicerrectorado consulte la oferta institucional. La interfaz
operativa es Blade; no es una SPA y no usa Inertia ni Vite.

## Flujo activo

- Director de Carrera: `/designaciones` lista las designaciones de su carrera.
  Puede crear una, copiar una anterior, editar su cabecera y sus asignaciones,
  buscar docentes y generar el PDF.
- Decanatura: `/designaciones` reutiliza el listado, detalle e impresión para las
  designaciones generales `APROBADO` de todas las carreras de su facultad. La
  consulta es de solo lectura y el servidor revalida el alcance al abrir el
  detalle o generar el PDF.
- Vicerrectorado: `/vicerrectorado/designaciones` permite consultar por gestión,
  abrir detalles y generar PDF. En el detalle puede aceptar/rechazar
  asignaciones docentes por fila y aprobar/observar la designación general sin
  cambiar esas filas. El estado general se calcula al decidir todas las filas;
  la observación general se guarda separada de las observaciones por fila. La
  persistencia requiere aplicar el contrato autorizado descrito en
  `docs/specs/changes/decisiones-vicerrectorado-jachasun/scripts-bd.sql`.
- Los flujos requieren autenticación. El rol, la carrera y la facultad se
  determinan en el servidor; no se confía en valores de alcance enviados por el
  navegador.
- `/health` ofrece el chequeo de salud. `/notificaciones` muestra las
  notificaciones del usuario autenticado.

No confunda las pantallas actuales con bocetos, propuestas antiguas ni specs
archivados. Las rutas registradas en `routes/web.php` son la referencia del
flujo disponible.

## Tecnología y límites

- PHP 8.3+, Laravel 13, PostgreSQL, Blade, Alpine.js, Tailwind CLI y DOMPDF.
- Jachasun es la fuente institucional de designaciones y datos académicos; el
  contrato de consultas y escrituras está en
  [`INTEGRATION_JACHASUN.md`](INTEGRATION_JACHASUN.md).
- Consultas externas parametrizadas; las consultas institucionales de lectura
  usan transacciones de solo lectura. Las escrituras autorizadas siguen el
  contrato de funciones de Jachasun.
- Autorización del lado servidor. La edición de cabeceras y datos académicos
  sigue limitada a Dirección de Carrera y se bloquea cuando la designación está
  `APROBADO`; Decanatura solo consulta/imprime designaciones aprobadas de su
  facultad; Vicerrectorado puede aceptar/rechazar asignaciones y aprobar u
  observar el estado general mediante las funciones autorizadas.
- No usar servicios externos reales ni datos personales reales en pruebas. No
  ejecutar migraciones destructivas fuera de una base local/testing autorizada.

## Reglas que no se deben inventar

Siga las reglas locales de `AGENTS.md`. En particular, no infiera grupos sin
una fuente autorizada; use el siguiente grupo consecutivo según la oferta y las
filas existentes. Al editar se conserva el grupo actual. Las horas son enteros
no negativos y no pueden exceder las horas oficiales; no se guarda una fila con
las tres categorías en cero. Si falta una decisión académica, deténgase y
marque `NEEDS_BUSINESS_CONFIRMATION`.

## Dónde cambiar

- `routes/web.php`: rutas y middleware.
- `app/Http/Controllers/`: autorización de solicitudes y respuestas.
- `app/Services/Jachasun/`: consultas, validaciones y escrituras institucionales.
- `resources/views/`: interfaz Blade y PDFs. `resources/assets/` contiene CSS y
  JavaScript propios, servidos por `/resources/assets/{path}` sin copia en
  `public/`. Tailwind se genera desde `resources/assets/css/tailwind.input.css`
  y `tailwind.generated.css` es el CSS entregado en producción.
- `database/migrations/`, `app/Models/`: datos locales y modelos.
- `tests/Feature/`, `tests/Unit/`: pruebas de comportamiento y servicios.

Antes de editar, siga `AGENTS.md`. Los controladores, servicios, migraciones y
pruebas son la fuente verificable del comportamiento.

## Verificación

Testing usa PostgreSQL aislado, no SQLite ni la base universitaria.

```bash
npm ci
npm run build:css
npm audit
php artisan test --env=testing
vendor/bin/pint --test
composer audit --locked
```

No ejecute la suite si `.env.testing` no apunta al PostgreSQL de testing
autorizado. Registre cierres y bloqueos en `testing/STATUS.md` y
`testing/TEST_MATRIX.md`.

## Documentación restante

- `INTEGRATION_JACHASUN.md`: contrato técnico externo.
- `HIGIENE_REPOSITORIO.md`: archivos publicables y material local.
- `testing/STATUS.md`, `testing/TEST_MATRIX.md` y `testing/BUG_REPORTS/`:
  estado, cobertura e informes de regresión.
- `specs/`: especificaciones de cambios.
- `archive/`: bitácoras, bocetos, planes y documentación histórica; no es fuente
  normativa vigente.

# Arquitectura y límites del sistema

## Fuente de verdad

La conducta del sistema se determina por el código ejecutable, las migraciones
y las pruebas. Esta guía explica esos límites; no crea reglas universitarias.
Cuando una decisión funcional no está confirmada se conserva la marca
`NEEDS_BUSINESS_CONFIRMATION`.

## Flujo principal

```text
Request
  -> Form Request (entrada y autorización)
  -> Controller (HTTP y respuesta)
  -> Service (caso de uso y transacción)
  -> Model/DB (invariantes y persistencia)
  -> vista o JSON
```

Los controladores no deben duplicar reglas de negocio. Las Policies y los
Form Requests autorizan antes de validar datos de recursos. Los Services
coordinan transacciones y los modelos/migraciones conservan restricciones.

## Límites de datos

La base local contiene usuarios, carreras, gestiones, propuestas, filas de
asignación, versiones, snapshots, decisiones, auditoría y notificaciones. Esta
base es la única que recibe migraciones y escrituras de la aplicación.

Jachasun es una fuente institucional externa y separada. Su primer uso es el
catálogo de `designaciones.f_asignaciones`; la integración se ejecuta en una
conexión secundaria, dentro de una transacción `READ ONLY`, y está desactivada
por defecto. No se importan docentes, materias, grupos u horas hasta confirmar
que la función entregue esos identificadores y su significado.

## Módulos

- Propuestas: crear borradores, editar filas, copiar e importar datos locales.
- Revisión: decidir snapshots pendientes y producir el estado resultante.
- Consulta: preparar datos para lista y edición sin cambiar reglas.
- Institucional: consultar y normalizar únicamente el catálogo autorizado.
- Notificaciones y auditoría: registrar eventos internos del flujo.

## Roles y fronteras

`director_carrera` opera únicamente su carrera y `vicerrectorado` accede a la
bandeja de revisión conforme a sus Policies. La consulta institucional aplica
la misma frontera: un director no puede consultar otra carrera; Vicerrectorado
puede usar el alcance global autorizado por el código actual.

## Estados independientes

No existe un estado único para todo el dominio. Se deben leer por separado:

| Objeto | Estados observados |
| --- | --- |
| Propuesta local | `borrador`, `oficial` |
| Versión enviada | `pendiente`, `retirada`, `observada`, `aprobada` |
| Fila actual | `propuesta`, `aprobada_previamente`, `oficial` según el flujo |
| Sistema legado | `propuesta`, `aprobada`, `rechazada` |
| Notificación | no leída, leída, según almacenamiento de Laravel |
| Snapshot/decisión | registros inmutables ligados a una versión |

Las transiciones exactas y sus efectos están descritas en
[`BUSINESS_RULES.md`](BUSINESS_RULES.md). No se debe documentar el estado
legado como si fuera el estado de una versión actual.

## Seguridad y fallos

- Credenciales solo en `.env`; nunca se registran ni se versionan.
- La conexión institucional no sustituye `DB_CONNECTION`.
- Las consultas institucionales usan parámetros enlazados.
- Los errores externos se muestran con mensajes seguros y se registran sin
  credenciales ni datos sensibles.
- Las pruebas usan PostgreSQL local/testing y datos sintéticos.

## Referencias de implementación

- `app/Services/PropuestaService.php`
- `app/Services/RevisionPropuestaService.php`
- `app/Services/ImportacionPropuestaService.php`
- `app/Policies/PropuestaPolicy.php`
- `app/Policies/PropuestaVersionPolicy.php`
- `app/Services/Institutional/InstitutionalDesignacionesService.php`
- `config/institutional.php`
- `database/migrations/`

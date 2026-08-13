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

Jachasun es la conexión principal de ejecución. La lista de designaciones usa
el catálogo `designaciones.f_asignaciones` dentro de una transacción `READ ONLY`.
La compatibilidad de tablas para propuestas, versiones, snapshots, decisiones,
auditoría y notificaciones debe verificarse contra el esquema real antes de
eliminar o cambiar sus modelos y reglas.

## Módulos

- Propuestas: crear borradores, editar filas, copiar e importar datos locales.
- Revisión: decidir snapshots pendientes y producir el estado resultante.
- Consulta: preparar datos para lista y edición sin cambiar reglas.
- Jachasun: consultar y normalizar únicamente el catálogo autorizado.
- Notificaciones y auditoría: registrar eventos internos del flujo.

## Roles y fronteras

`director_carrera` opera únicamente su carrera y `vicerrectorado` accede a la
bandeja de revisión conforme a sus Policies. La lista Jachasun obtiene la
carrera exclusivamente del usuario autenticado.

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
- `DB_CONNECTION=jachasun` es la conexión principal.
- Las consultas Jachasun usan parámetros enlazados.
- Los errores externos se muestran con mensajes seguros y se registran sin
  credenciales ni datos sensibles.
- Las pruebas usan PostgreSQL local/testing y datos sintéticos.

## Referencias de implementación

- `app/Services/PropuestaService.php`
- `app/Services/RevisionPropuestaService.php`
- `app/Services/ImportacionPropuestaService.php`
- `app/Policies/PropuestaPolicy.php`
- `app/Policies/PropuestaVersionPolicy.php`
- `app/Services/Jachasun/JachasunDesignacionesService.php`
- `database/migrations/`

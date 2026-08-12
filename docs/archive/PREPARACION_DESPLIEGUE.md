# Preparacion para despliegue e integracion institucional

## Objetivo

Preparar Designaciones UATF para ejecutarse en servidores de la universidad sin
conectar directamente la aplicacion a tablas institucionales ni arriesgar datos
reales. La aplicacion conservara una base propia para el flujo de propuestas,
revision, snapshots, usuarios, auditoria y notificaciones.

La informacion institucional se incorporara progresivamente mediante funciones
PostgreSQL autorizadas y una conexion separada de la conexion principal de
Laravel.

## Estado inicial verificado

Fecha: 2026-08-09.

| Elemento | Estado actual | Regla de seguridad |
| --- | --- | --- |
| Aplicacion local | PostgreSQL en `127.0.0.1:5432` | No usar para datos institucionales |
| Testing | PostgreSQL aislado en `127.0.0.1:55432` | Solo datos sinteticos |
| Funcion institucional | No integrada | No agregar credenciales reales aun |
| Conexion institucional | No existe | Nunca debe reemplazar `DB_CONNECTION` |
| Backend | Laravel 13, PHP requerido `^8.3` | Verificar version del servidor antes de instalar |
| Frontend | Blade, Alpine y Tailwind por CDN | Definir politica CDN antes de red aislada |
| Funciones disponibles | Ejemplo `designaciones.f_asignaciones(...)` | Documentar parametros antes de consumirlos |
| CodeGraph | Indice presente, consulta bloqueada por SQLite | No borrar `codegraph.db`; usar busqueda nativa hasta resolver servicio |

## Ambientes

Se deben mantener separados y con credenciales diferentes:

1. **Local**: desarrollo individual y datos de prueba.
2. **Testing**: pruebas automatizadas y navegacion manual con datos sinteticos.
3. **Staging**: replica controlada del servidor universitario, sin usuarios reales.
4. **Produccion**: datos institucionales y acceso operativo.
5. **Institucional**: fuente externa de funciones autorizadas; no es la base
   principal de Laravel.

Reglas obligatorias:

- Las migraciones de Laravel se ejecutan solo sobre la base propia de la
  aplicacion.
- Los seeders de testing nunca se ejecutan contra staging, institucional o
  produccion.
- `migrate:fresh` queda limitado a bases locales o testing expresamente
  identificadas.
- Las credenciales se mantienen fuera de Git y nunca se copian entre ambientes.
- La aplicacion no recibe permisos `SELECT` sobre tablas institucionales.
- La conexion institucional solo podra ejecutar funciones incluidas en una
  lista permitida.

## Cambios locales pendientes de clasificacion

Estos cambios ya estaban presentes al iniciar la preparacion y no pertenecen a
la integracion institucional:

| Archivo | Contenido | Accion |
| --- | --- | --- |
| `app/Http/Controllers/PropuestaController.php` | Docentes asignados primero y ordenados | Revisar con pruebas y decidir commit separado |
| `resources/views/designaciones/carrera.blade.php` | Enlace a Mis propuestas y texto de aprobacion | Revisar visualmente y decidir commit separado |
| `resources/views/revisiones/pendientes.blade.php` | Retiro de checkboxes sin comportamiento | Confirmar con el usuario antes de integrar |

No se sobrescribiran ni se mezclaran automaticamente con las fases de
despliegue.

## Inventario inicial del servidor requerido

Antes de instalar en la universidad se debe obtener una ficha con:

- sistema operativo y arquitectura;
- PHP y extensiones disponibles;
- Composer;
- PostgreSQL para la base propia de la aplicacion;
- Nginx/PHP-FPM o IIS;
- dominio y certificado HTTPS;
- usuario de servicio sin privilegios administrativos;
- permisos para `storage` y `bootstrap/cache`;
- cola, cache, sesiones y correo;
- politica de firewall y salida hacia el servidor institucional;
- ruta y frecuencia de respaldos;
- procedimiento de restauracion;
- rotacion y retencion de logs;
- ventana autorizada para migraciones y mantenimiento.

No se elegira Nginx o IIS hasta recibir esta informacion del administrador del
servidor.

## Proceso seguro para funciones institucionales

Cada funcion recibida debe registrarse antes de implementarse:

```text
nombre completo
parametros y tipos
significado de cada valor
columnas de salida y tipos
identificadores estables
reglas de nulos y duplicados
errores y timeouts
permisos
frecuencia de actualizacion
version del contrato
lectura o escritura
ejemplo sintetico
```

El ejemplo `designaciones.f_asignaciones('CIV', '0', '0')` no se consumira
hasta confirmar que significan `CIV`, `0` y `0`, y que las columnas devueltas
incluyen claves estables.

## Contrato observado de `f_asignaciones` (avance de Fase 4)

El responsable funcional confirmó los parámetros de la consulta:

```sql
SELECT * FROM designaciones.f_asignaciones('INF', '2023', '1');
SELECT * FROM designaciones.f_asignaciones('UATF', '2024', '1');
```

- `INF` representa el código de una carrera.
- `UATF` representa la universidad completa y devuelve varias carreras.
- El segundo parámetro es la gestión (por ejemplo, `2023` o `2024`).
- El tercer parámetro es el periodo/semestre (por ejemplo, `1`).

La salida observada contiene estas columnas:

| Columna | Tipo observado | Uso provisional |
| --- | --- | --- |
| `r_id` | entero | Identificador retornado por la fuente |
| `r_id_programa` | texto | Código de carrera/programa |
| `r_programa` | texto | Nombre de carrera/programa |
| `r_detalle` | texto | Descripción del periodo o actividad |
| `r_fecha` | timestamp | Fecha retornada por la fuente |
| `r_id_gestion` | entero | Gestión retornada |
| `r_id_periodo` | entero | Periodo retornado |
| `r_obs` | texto | Observación, por ejemplo `MIGRADO` |
| `r_estado` | texto | Estado, por ejemplo `SOLICITADO` |

Esta salida es un catálogo de registros de programa/gestión/periodo; no
incluye todavía docente, materia, grupo ni horas, por lo que no se puede
mapear directamente al editor de asignaciones local. La aplicación incorpora
por ahora un cliente de lectura y el endpoint autenticado
`GET /institucional/designaciones`, sin escribir en la fuente institucional.

Queda `NEEDS_BUSINESS_CONFIRMATION` verificar por qué la salida entregada para
`INF` contiene varias gestiones y periodos pese a la consulta indicada, además
de confirmar si `r_id` es estable y cuál es el significado operativo de cada
estado. No se debe usar este catálogo para importar filas docentes hasta
resolver esas ambigüedades.

La configuración activa de esta integración vive en
`config/institutional.php`. `INSTITUTIONAL_ENABLED=false` sigue siendo el
valor predeterminado y `DB_CONNECTION` continúa apuntando exclusivamente a la
base propia de la aplicación.

## Fases siguientes

- **Fase 2**: guia de arquitectura y capacitacion practica. Completada en
  `docs/GUIA_APRENDIZAJE.md`.
- **Fase 3**: instalacion reproducible, HTTPS, permisos, respaldos y health
  checks. Completada en `docs/GUIA_DESPLIEGUE.md` y con `app:health`.
- **Fase 4**: contrato y adaptador de funciones en entorno simulado.
- **Fase 5**: proyeccion local e importacion idempotente de lectura.
- **Fase 6**: funciones institucionales de escritura, una capacidad por vez.
- **Fase 7**: integridad, rollback, concurrencia, staging y aceptacion.

No se habilitara una funcion institucional real antes de completar su contrato,
mock, prueba, `dry-run`, prueba en staging y procedimiento de rollback.

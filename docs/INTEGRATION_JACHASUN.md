# Integracion de Jachasun: catalogo de asignaciones

## Contrato observado

La unica funcion autorizada actualmente es:

```sql
SELECT * FROM designaciones.f_asignaciones(?, ?, ?);
```

Ejemplos conocidos:

```sql
SELECT * FROM designaciones.f_asignaciones('INF', '2023', '1');
SELECT * FROM designaciones.f_asignaciones('UATF', '2024', '1');
SELECT * FROM designaciones.f_asignaciones('INF', '0', '0');
```

- Primer parametro: codigo de carrera/programa (`INF`) o universidad completa
  (`UATF`), segun la autorizacion vigente.
- Segundo parametro: gestion, por ejemplo `2023` o `2024`.
- Tercer parametro: periodo/semestre, por ejemplo `1`.
- Gestion y periodo `0` solicitan todos los registros disponibles del programa.

La respuesta contiene `r_id`, `r_id_programa`, `r_programa`, `r_detalle`,
`r_fecha`, `r_id_gestion`, `r_id_periodo`, `r_obs` y `r_estado`. La aplicacion
normaliza esos campos como `id`, `programa_codigo`, `programa_nombre`,
`detalle`, `fecha`, `gestion`, `periodo`, `observacion` y `estado`.

## Implementacion actual

- Servicio unico: `InstitutionalDesignacionesService`.
- Lista principal: `GET /designaciones`.
- Endpoint JSON: `GET /institucional/designaciones`.
- Pantalla protegida: `GET /institucional/designaciones/consulta`.
- Conexion secundaria configurada en `config/institutional.php`.
- Parametros enlazados, sin SQL construido con entrada del usuario.
- Cada consulta usa `SET TRANSACTION READ ONLY`.
- `INSTITUTIONAL_ENABLED=false` es el valor predeterminado.
- No hay migraciones, inserts, updates, deletes ni importacion hacia Jachasun.

`/designaciones` consulta siempre la sigla de la carrera del director autenticado
con gestion y periodo `0`/`0`. No mezcla propuestas locales ni usa datos locales
como respaldo. Si Jachasun esta deshabilitado, falla o no devuelve un contrato
valido, la pantalla responde HTTP 503 con un mensaje seguro.

La tabla reutiliza la interfaz principal y muestra el identificador, detalle,
fecha, gestion, periodo, observacion y estado. Las acciones se conservan
visibles como controles deshabilitados hasta definir sus operaciones
institucionales. El codigo y nombre del programa permanecen en el encabezado.

La equivalencia visual provisional de `r_estado=SOLICITADO` con `Oficial` esta
marcada como `NEEDS_BUSINESS_CONFIRMATION`; cualquier estado desconocido se
muestra sin inventar una equivalencia.

## Limite funcional actual

La salida sigue siendo un catalogo de registros de programa/gestion/periodo.
No se ha confirmado que incluya identificadores de docente, materia, grupo y
horas; por eso no se convierte en filas locales ni se usa para importar
designaciones.

## Seguridad

Las credenciales viven exclusivamente en `.env` del ambiente autorizado. Los
errores de conexion, permisos o contrato no exponen SQL, credenciales ni filas
en la respuesta o los logs. La validacion real queda limitada a desarrollo o
staging autorizado; la suite local usa mocks.

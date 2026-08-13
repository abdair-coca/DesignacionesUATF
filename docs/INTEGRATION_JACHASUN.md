# Integracion de Jachasun

## Contrato disponible

La unica funcion utilizada por la aplicacion es:

```sql
SELECT * FROM designaciones.f_asignaciones(?, ?, ?);
```

Ejemplos conocidos:

```sql
SELECT * FROM designaciones.f_asignaciones('INF', '2023', '1');
SELECT * FROM designaciones.f_asignaciones('UATF', '2024', '1');
SELECT * FROM designaciones.f_asignaciones('INF', '0', '0');
```

- Primer parametro: codigo de carrera o programa.
- Segundo parametro: gestion de cuatro digitos o `0`.
- Tercer parametro: periodo numerico o `0`.
- Gestion y periodo `0` solicitan todos los registros disponibles del programa.

La respuesta conocida contiene `r_id`, `r_id_programa`, `r_programa`,
`r_detalle`, `r_fecha`, `r_id_gestion`, `r_id_periodo`, `r_obs` y `r_estado`.
El servicio las normaliza como `id`, `programa_codigo`, `programa_nombre`,
`detalle`, `fecha`, `gestion`, `periodo`, `observacion` y `estado`.

## Flujo actual

1. Un director autenticado entra a `GET /designaciones`.
2. La carrera se obtiene del usuario autenticado, nunca de la entrada del navegador.
3. `JachasunDesignacionesService` ejecuta `f_asignaciones(sigla, '0', '0')`.
4. La consulta usa parametros enlazados dentro de una transaccion `READ ONLY`.
5. `PropuestaConsultaService` adapta las filas al contrato que ya usa la tabla.
6. `resources/views/designaciones/lista.blade.php` muestra los resultados.

La pantalla no mezcla filas locales ni convierte resultados Jachasun en propuestas.
Las acciones visibles permanecen deshabilitadas hasta confirmar funciones que
entreguen docentes, materias, grupos y horas.

## Configuracion

La aplicacion usa una unica conexion PostgreSQL llamada `jachasun`, configurada
con `DB_*` en `.env`:

```env
DB_CONNECTION=jachasun
DB_HOST=...
DB_PORT=5432
DB_DATABASE=jachasun
DB_USERNAME=...
DB_PASSWORD=...
```

No existe una bandera de activacion ni una conexion secundaria institucional.
Las credenciales no se versionan ni se escriben en logs.

## Limites

Solo esta confirmado el catalogo de `f_asignaciones`. No se documentan como
compatibles otras tablas o funciones de Jachasun hasta contar con evidencia de
esquema, permisos y reglas. Cualquier ampliacion queda marcada como
`NEEDS_BUSINESS_CONFIRMATION`.

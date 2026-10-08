# BUG-2026-09-29: Bandeja de Vicerrectorado vacia con todos los periodos

Estado: **RESUELTO Y VERIFICADO EN PRODUCCION**

## Reproduccion

En produccion, la pantalla consultaba la fuente institucional con:

```sql
SELECT * FROM designaciones.f_asignaciones('UATF', '2026', '0');
```

La consulta respondia cero filas. Las consultas equivalentes para los periodos
`1` y `2` respondian seis filas cada una.

## Causas

La funcion institucional no interpreta el periodo `0` como comodin para todos
los periodos. La aplicacion lo habia tratado como si devolviera el alcance
completo. Durante la correccion tambien se detecto que la consulta combinada
enviaba saltos de linea literales (`\\n`) a PostgreSQL.

## Correccion

`JachasunDesignacionesService::listarUniversidadPaginado()` ahora combina las
consultas de los periodos institucionales `1` y `2` dentro de una transaccion
`READ ONLY`, conserva todos los estados y ordena por fecha descendente.
La consulta se construye con saltos de linea reales antes de enviarse al
servidor.

## Regresion

`tests/Unit/VicerrectoradoDesignacionesServiceTest.php` exige dos llamadas a
`f_asignaciones`, con los periodos `1` y `2`, y verifica el orden y los estados.

## Verificacion

- Consulta directa en produccion antes de la correccion: `0` filas para periodo
  `0`; `6` para periodo `1`; `6` para periodo `2`.
- Regresion del servicio despues de la correccion: OK; 1 prueba, 7 aserciones.
- Verificacion final de la consulta de aplicacion en produccion: OK; 12 filas
  totales para `2026`, 10 en la primera pagina, fecha inicial
  `2026-09-21 15:19:21.467666`.

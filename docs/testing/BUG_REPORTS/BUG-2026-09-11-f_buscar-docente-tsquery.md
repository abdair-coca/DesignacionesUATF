# BUG-2026-09-11: busqueda de docente falla con signos de tsquery

Estado: **PENDIENTE; no se modifico codigo**

## Funcion afectada

`public.f_buscar_docente(text)`.

## Reproduccion

En la conexion autorizada de lectura:

```sql
SELECT count(*)
FROM (SELECT * FROM public.f_buscar_docente(':')) filas;
```

Resultado: error SQLSTATE `42601`.

La misma falla se reproduce con `(`. La entrada `INF`, `inf`, vacia, espacios,
`NULL` y un termino inexistente no genera error.

## Causa observada

La funcion transforma la entrada y la envia directamente a
`to_tsquery('spanish', ...)`. Algunos signos reservados se interpretan como
sintaxis invalida de `tsquery`.

## Impacto

Una pantalla de busqueda que envie texto libre puede recibir un error de base de
datos ante determinados caracteres. La funcion no debe exponerse sin validacion
o normalizacion previa de la entrada.

## Alcance de esta fase

Se realizaron solamente consultas de lectura. No se modifico la funcion, la
aplicacion, permisos ni datos. La prueba de regresion automatizada queda
pendiente de una fase autorizada de correccion.

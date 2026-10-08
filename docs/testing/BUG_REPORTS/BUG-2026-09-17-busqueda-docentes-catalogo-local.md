# BUG-2026-09-17: Busqueda de docentes depende de indice externo

## Estado

RESUELTA.

## Ambiente

Testing local con datos sinteticos.

## Reproduccion

- Autenticar un director de carrera.
- Crear un docente local con nombre acentuado y CI conocido.
- Consultar `GET /designaciones/docentes/buscar?q=ada%20perez`.
- La ruta actual delega en `public.f_buscar_docente`; el docente local no se
  encuentra y la prueba recibe una respuesta de error o un resultado externo.

## Resultado esperado

La ruta consulta `App\Models\Docente`, encuentra por nombre, apellido o CI y
devuelve unicamente `id`, `nombre` y `ci`.

## Causa observada

La primera correccion consultaba `App\Models\Docente` sobre `public.docentes`.
En la conexion desplegada esa tabla no existe; el catalogo local autorizado esta
en `academico.docentes`, con `id_docente`, `nombres`, `paterno`, `materno` y `ci`.
Por eso la ruta devolvia el mensaje generico de error.

## Regresion

`JachasunDesignacionesDetailTest::test_busqueda_de_docentes_usa_el_catalogo_local_por_nombre_y_ci`

## Correccion

- `JachasunDesignacionesService::buscarDocentes()` consulta `Docente` en
  transaccion de solo lectura y usa solo `id`, `nombre` y `ci`.
- La busqueda normaliza tildes, mayusculas, signos y espacios; todos los tokens
  ingresados deben coincidir en nombre o CI.
- El servicio ordena por nombre e id, elimina duplicados y limita a 100 filas.
- El controlador filtra de nuevo la respuesta al contrato publico y mantiene el
  mensaje generico ante errores.

## Verificacion

- Regresion local: pasa.
- Busqueda por nombre/apellido, tildes, espacios y CI: pasa.
- Limite, orden y unicidad: pasa.
- Termino vacio sin consulta: pasa.
- No se ejecuta `public.f_buscar_docente` en este flujo.
- En la conexion desplegada se verifico que `public.docentes` no existe y que
  `academico.docentes` si existe.
- Smoke de lectura real: 31 coincidencias sinteticas de `Ada`; la respuesta solo
  contiene `id`, `nombre` y `ci`.

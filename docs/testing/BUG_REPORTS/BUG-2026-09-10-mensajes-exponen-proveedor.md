# BUG-2026-09-10: mensajes visibles exponen detalles técnicos

## Estado

Resuelto.

## Reproducción

1. Provocar un error al consultar o actualizar designaciones.
2. Revisar el mensaje mostrado al usuario.
3. Observar referencias al proveedor técnico o un trato informal.

## Causa

Los mensajes de excepción y algunas confirmaciones de la interfaz se mostraban directamente o utilizaban lenguaje no uniforme.

## Corrección

Los usuarios reciben mensajes genéricos y formales. Los detalles técnicos quedan únicamente en el registro controlado de la aplicación.

## Regresión

`JachasunDesignacionesListTest::test_error_de_lista_no_menciona_el_proveedor_tecnico`

`JachasunDesignacionesEscrituraTest`

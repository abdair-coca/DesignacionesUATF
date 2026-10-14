# BUG-2026-10-14-pagina-director-jachasun: prueba de lista del Director espera un texto que ya no existe

## Severidad

P3 — la prueba de la lista del Director fallaba y además tenía una aserción vacía.

## Módulo

Pruebas de acceso a páginas (`tests/Feature/PageAccessTest.php`) y lista de
designaciones del Director.

## Ambiente

PostgreSQL de testing aislado, base limpia y migrada.

## Precondiciones

Código de `main` (`0a7c527`) y de las fases P1 a P5.

## Pasos para reproducir

1. Ejecutar `PageAccessTest::test_director_ve_lista_jachasun_y_rutas_locales_no_existen`.
2. Observar la falla de `assertSee('Designaciones de Jachasun')`.

## Resultado actual antes de corregir

La página del Director respondía 200 con el título "Lista de Designaciones — UATF".
La aserción buscaba "Designaciones de Jachasun", que no aparece en ninguna vista.

## Resultado esperado

La prueba verifica el texto vigente de la lista y que las rutas locales antiguas
siguen sin existir.

## Causa raíz

Verificada: ninguna vista contenía "Designaciones de Jachasun". El texto aparece
por última vez en el historial en `2297950` ("Integra lista principal con
Jachasun") y en `8e137d5` ("chore: sincronizar estado actual del proyecto"). La
prueba no se actualizó cuando cambió el texto.

Además, `assertDontSee('Nueva Propuesta de Designaci�n')` tenía U+FFFD en lugar de
`ó`. Esa aserción nunca encontraba su texto, así que pasaba siempre y no verificaba
nada.

## Descartado

- Regresión de la ruta: `/designaciones` responde 200 y las rutas locales antiguas
  responden 404 como se espera.

## Corrección aplicada (14/10/2026)

- La prueba espera el título vigente, "Lista de Designaciones". No se cambió la
  interfaz.
- La aserción negativa usa el texto con `ó`, así que ahora sí puede fallar.

Decisión registrada: si se quiere volver a "Designaciones de Jachasun", es un
cambio de la vista y de esta prueba, y debe confirmarse antes.

## Prueba de regresión

- `PageAccessTest::test_director_ve_lista_jachasun_y_rutas_locales_no_existen` pasa.

## Estado

Corregido el 14/10/2026.

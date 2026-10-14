# BUG-2026-10-14-aislamiento-jachasun: filas confirmadas rompen pruebas posteriores

## Severidad

P2 — oculta el resultado real de la suite. Era el origen de 40 de las 53 fallas
y errores que existían en `main`.

## Módulo

Pruebas de Feature de designaciones Jachasun.

## Ambiente

PostgreSQL de testing aislado en `127.0.0.1:55432`, base
`designaciones_uatf_testing`. Suite completa con `php artisan test --env=testing
--no-coverage`, con la base reiniciada y migrada.

## Precondiciones

Base vacía. Código de `main` (`0a7c527`) y de las fases P1 a P5.

## Pasos para reproducir

1. Reiniciar la base de testing y ejecutar `php artisan migrate --env=testing --force`.
2. Ejecutar la suite completa.
3. Observar 40 errores `SQLSTATE[23505]` sobre `carreras_sigla_unique`
   (`Key (sigla)=(INF) already exists`): 19 en `JachasunDesignacionesDetailTest`,
   13 en `JachasunDesignacionesEscrituraTest` y 8 en `JachasunDesignacionesListTest`.
4. Consultar `carreras` y `users` después de la corrida: quedan filas confirmadas.

## Resultado actual antes de corregir

Las pruebas de detalle y escritura confirmaban filas en la base de testing. Las
pruebas siguientes que creaban la carrera `INF` fallaban por llave única, antes
de llegar a sus aserciones.

## Resultado esperado

Cada prueba deja la base como la encontró. Las pruebas se ejecutan y pasan, o
fallan por una aserción real.

## Causa raíz

Verificada: `JachasunDesignacionesDetailTest` y `JachasunDesignacionesEscrituraTest`
definían `protected $connectionsToTransact = [];`, lo que desactivaba la transacción
de prueba de `DatabaseTransactions`. Sus pruebas creaban carreras y usuarios con
factories, y esas filas quedaban confirmadas.

No verificada: por qué se añadió la desactivación. La hipótesis de que
`SET TRANSACTION READ ONLY` del adaptador de lectura falla dentro de una
transacción externa quedó descartada: PostgreSQL 17 la acepta, también después de
consultas dentro de un savepoint.

## Corrección aplicada (14/10/2026)

- Se quitó `$connectionsToTransact = []` de las dos clases. Ninguna prueba dependía
  de filas confirmadas: las dos clases mockean el servicio de Jachasun.
- Al quitar la protección, las 40 pruebas dejaron de fallar por llave única y
  quedaron visibles 12 fallas que antes estaban ocultas. Se ajustaron así, según el
  código vigente:
  - 5 de lista: la paginación y la búsqueda ocurren en el navegador
    (`designaciones-filtros.js`). Las pruebas mockean `listar` y verifican ese
    contrato. Las pruebas que pedían paginación en el servidor se reescribieron.
  - 2 de log: esperaban textos que el controlador no escribe. Ahora esperan los
    textos reales.
  - 4 de la modal de detalle: sus textos viven en
    `resources/assets/js/designaciones/carrera.js`. Se verifican en ese archivo; la
    página sigue cargando el script.
  - 1 de impresión: esperaba dos enlaces a `/pdf`. La vista tiene uno en el
    encabezado y las filas usan el botón de carta. Se espera exactamente uno.

## Prueba de regresión

- Las 40 pruebas de `JachasunDesignacionesDetailTest`, `JachasunDesignacionesEscrituraTest`
  y `JachasunDesignacionesListTest` ahora se ejecutan y pasan: 68/68 en los cinco
  archivos afectados.
- Tras la corrida completa, `carreras` y `users` quedan sin filas.

## Límites

- Las verificaciones de JavaScript comprueban el texto del archivo fuente, no el
  comportamiento en un navegador.
- Las pruebas de paginación comprueban el contrato (todas las filas al navegador,
  10 por página en el script), no la paginación real en pantalla.

## Estado

Corregido el 14/10/2026.

# BUG-2026-10-14-carta-director-pruebas-desfasadas: pruebas de carta del Director no ejecutan el flujo real

## Severidad

P3 — las pruebas de regresión del Director no validan la carta. El código del
Director no tiene fallas conocidas.

## Módulo

Pruebas de la carta de designación del Director (`tests/Feature/DesignacionPdfTest.php`).

## Ambiente

PostgreSQL de testing aislado, base limpia y migrada. Ejecución con
`php artisan test --env=testing --no-coverage tests/Feature/DesignacionPdfTest.php`.

## Precondiciones

Código de `main` (`0a7c527`) y de las fases P1 a P5.

## Pasos para reproducir

1. Ejecutar `DesignacionPdfTest` sola en una base limpia.
2. Observar 6 fallas y 3 errores de 15 pruebas.

## Resultado actual

- `422` donde las pruebas esperan `200`, `404` o `503`: 6 respuestas.
- `Undefined array key "fecha_resolucion_anio"` al renderizar
  `designaciones/carta-pdf.blade.php`: 3 errores.

## Resultado esperado

Las 15 pruebas pasan con la carta del Director tal como está en el código.

## Causa raíz

Verificada, en las pruebas:

1. El helper `datosCarta()` de `DesignacionPdfTest` no envía `fila_id`.
   `GenerarCartaDesignacionRequest` exige `fila_id` desde `0a7c527`, así que cada
   envío responde `422` antes de llegar al controlador.
2. Algunas pruebas renderizan la vista con arreglos armados a mano que no tienen
   `fecha_resolucion_anio`. `PrepararCartaDesignacion` sí devuelve esa clave en el
   flujo real.

El código del Director no tiene diferencias contra `HEAD` en el controlador, el
`FormRequest`, el servicio de preparación, la vista ni el modal.

## Descartado

- Falla en `DesignacionController::cartaPdf` o en la vista: el flujo real
  renderiza. Las pruebas de carta de Personal, que usan el mismo servicio y la
  misma vista, generan el PDF y pasan.

## Prueba de regresión

- Las 15 pruebas de `DesignacionPdfTest` son la reproducción.
- Corrección sugerida: añadir `fila_id` al helper y `fecha_resolucion_anio` a los
  arreglos de las pruebas de vista.

## Estado

Aceptado tal como está, por decisión del usuario el 14/10/2026. Sin corrección por ahora.
Sigue abierto como deuda de pruebas: no afecta el código del Director ni el flujo de Personal.

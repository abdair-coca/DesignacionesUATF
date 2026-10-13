# BUG-2026-10-09: autorización de carta PDF antes de validar datos

Estado: **CORREGIDO; regresión dirigida aprobada**

## Reproducción

Enviar `POST /designaciones/{id}/carta-pdf` con una sesión autenticada de
Decanatura y datos incompletos.

## Resultado

La solicitud respondía HTTP 422 porque el formulario validaba sus campos antes
de que el controlador negara el acceso. El endpoint debe responder HTTP 403 a
roles distintos de Dirección de Carrera, sin revelar si el payload es válido.

## Causa y corrección

`GenerarCartaDesignacionRequest::authorize()` solo comprobaba que hubiera una
sesión autenticada. Ahora autoriza exclusivamente a Dirección de Carrera antes
de ejecutar las reglas de validación. El controlador conserva también su
comprobación del rol.

## Regresión y verificación

- Regresión existente: `DecanaturaDesignacionesTest::test_decanatura_no_abre_detalle_fuera_de_su_facultad_o_no_aprobado` verifica el 403 del POST.
- La suite completa reprodujo la diferencia 422/403 antes de la corrección. Tras ajustar el `FormRequest`, `php artisan test --env=testing --no-coverage tests/Feature/DecanaturaDesignacionesTest.php --filter=test_decanatura_no_abre_detalle_fuera_de_su_facultad_o_no_aprobado` pasó: 1 prueba, 4 aserciones. La suite completa sigue bloqueada por indisponibilidad del PostgreSQL aislado de testing; ver `docs/testing/STATUS.md`.

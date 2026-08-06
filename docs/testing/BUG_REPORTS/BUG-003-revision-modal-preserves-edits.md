# BUG-003: Revision sin motivo recarga y pierde cambios del vicerrectorado

## Severidad

P1 - Critico para flujo de revision y experiencia de usuario.

## Modulo

Revision de propuestas, formulario del vicerrectorado.

## Ambiente

Testing local, PostgreSQL `127.0.0.1:55432`, dataset limpio para reproduccion.

## Precondiciones

- Existe una version pendiente con al menos dos filas revisables.
- Usuario autenticado con rol `vicerrectorado`.

## Pasos para reproducir

1. Abrir `/revisiones/{version}/revisar`.
2. Seleccionar `Observar` para una fila.
3. Dejar vacio el motivo y editar otros campos del formulario.
4. Pulsar `Confirmar Revision`.

## Resultado anterior

El navegador enviaba el formulario, el servidor rechazaba la decision por falta de motivo y la respuesta redirigia sin modal explicativo. La vista no reutilizaba los valores `old(...)`, por lo que se perdian las selecciones y textos editados.

## Resultado esperado

Un modal explica que el motivo es obligatorio. El formulario no se envia cuando falta motivo y conserva todo lo editado. La validacion del servidor permanece activa como respaldo.

## Causa raiz

`resources/views/versiones/revisar.blade.php` tenia un formulario HTML tradicional sin guardia `onsubmit`, modal de error ni repoblacion de campos desde la sesion de validacion.

## Prueba de regresion

`tests/Feature/PropuestaRevisionTest.php::test_revision_muestra_modal_y_conserva_edicion_si_falta_motivo`.

## Correccion

- Se agrego validacion cliente antes del POST.
- Se agrego modal `modal-error-revision`.
- Se conservaron observacion general, decisiones y motivos mediante `old(...)`.
- Se mantuvo validacion de `RevisionPropuestaService`.

## Estado

Mitigado y cubierto por prueba.

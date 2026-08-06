# BUG-004: Modal de validacion de revision no se ocultaba

## Severidad

P1 - Afecta el flujo principal de revision y bloquea la continuidad del usuario.

## Modulo

Revision de propuestas, modal de validacion del Vicerrectorado.

## Ambiente

Testing local, Laravel en `127.0.0.1:8000`, dataset sintetico, usuario
`vicerrectorado.testing@example.test`.

## Precondiciones

- Existe una version pendiente con al menos una fila revisable.
- La fila tiene decision `Observar` y no tiene motivo por fila ni observacion general.

## Pasos para reproducir

1. Abrir `/revisiones/{version}/revisar`.
2. Cambiar una fila a `Observar`.
3. Pulsar `Confirmar Revision` sin escribir un motivo.
4. Pulsar `Entendido` en el modal.

## Resultado anterior

El modal tenia el atributo HTML `hidden`, pero tambien la clase Tailwind `flex`.
La regla visual de `flex` podia prevalecer sobre el comportamiento esperado de
`hidden`: el modal aparecia al cargar o permanecia visible despues de cerrarlo.

## Resultado esperado

El modal inicia oculto, aparece solo cuando falta un motivo, se puede cerrar con
`Entendido`, `Cerrar` o `Escape`, y conserva las decisiones y textos editados.

## Causa raiz

El atributo `hidden` no se sincronizaba con una clase visual de ocultamiento.
La clase `flex` del mismo elemento mantenia el contenedor visible.

## Prueba de regresion

`tests/Feature/PropuestaRevisionTest.php::test_revision_muestra_modal_y_conserva_edicion_si_falta_motivo`.

## Correccion

- Se agrego la clase `hidden` al modal desde el render inicial.
- `abrirModalErrorRevision` elimina la clase `hidden`.
- `cerrarModalErrorRevision` agrega la clase `hidden` ademas de actualizar la propiedad `hidden`.

## Evidencia manual

- Antes: `initialVisible` era verdadero o el modal seguia visible despues de `Entendido`.
- Despues: `initialVisible=false`, el modal abre al confirmar, y despues de cerrar
  `modalVisibleAfterClose=false`; la decision observada y el motivo siguen editables.

## Estado

Resuelto y cubierto por prueba automatizada y navegador.

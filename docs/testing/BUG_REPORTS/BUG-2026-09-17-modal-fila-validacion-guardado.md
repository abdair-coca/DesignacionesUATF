# BUG-2026-09-17: Fila usa confirmacion y no valida dentro del modal

## Estado

RESUELTA.

## Ambiente

Testing local mediante render de la vista.

## Reproduccion

- Abrir una nueva asignacion o una reasignacion.
- Pulsar Guardar con docente, materia, grupo u horas incompletos.
- El estado actual no tiene `erroresFila` ni `validarFila()` y el boton llama a
  `confirmarEdicionFila()`, por lo que abre una confirmacion adicional y no
  conserva el contexto de validacion en el modal.

## Resultado esperado

El modal valida los campos, muestra mensajes formales dentro de la vista y solo
envia el formulario si los datos son validos. La confirmacion permanece solo para
la cabecera.

## Causa observada

El boton de fila delega siempre en `abrirConfirmacion()` y no existe estado de
errores ni una validacion local antes del POST.

## Regresion

`JachasunDesignacionesDetailTest::test_modal_valida_la_fila_y_guarda_sin_confirmacion_adicional`

## Correccion

- Se agrego `erroresFila` y `validarFila()` para mantener los errores dentro del
  modal y validar IDs, enteros no negativos, horas oficiales y horas todas en
  cero antes de enviar.
- El boton de fila llama a `guardarFila()` y usa `requestSubmit()`; ya no llama
  a `confirmarEdicionFila()`.
- La confirmacion de `confirmarEdicionCabecera()` no cambio.
- Los controles visibles siguen asociados a `form-editar-fila` y los
  identificadores se conservan en inputs ocultos.

## Verificacion

- Regresion de validacion y confirmacion: pasa.
- Regresion de payload y precarga: pasa.
- Guardado HTTP con los siete campos: pasa.
- Mensajes de excepcion del servidor: siguen genericos y seguros.

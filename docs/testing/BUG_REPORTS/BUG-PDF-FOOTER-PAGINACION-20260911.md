# Bug: footer de PDF sin reserva ni paginacion real

## Reproducción

- Vista: `resources/views/designaciones/pdf.blade.php`.
- Generador: `DesignacionController::pdf` mediante `Pdf::loadView(...)->stream(...)`.
- Estado inicial: footer fijo con línea continua, sin zona inferior suficiente para la tabla y contador CSS que producía `1 / 0`.
- Evidencia inicial: `/tmp/opencode/footer-before-single.png` y `/tmp/opencode/footer-before-multi-00.png`.

## Corrección

- `@page` conserva Carta y reserva `70pt` inferiores.
- La tabla mantiene `thead` como `table-header-group`, `tbody` como `table-row-group` y filas sin corte interno.
- El footer permanece fijo a `10pt`, con textos actuales sin cambios.
- La línea azul se divide en 47%/6%/47% y la roja ocupa 83% centrada.
- El controlador ejecuta `render()`, obtiene el Canvas y usa `page_script` con los números reales y `FontMetrics` para centrar `N / total`.

## Verificación

- Una página: `/tmp/opencode/footer-verified-single.pdf`, contador `1 / 1`.
- Cuatro páginas: `/tmp/opencode/footer-verified-multi.pdf`, primera `1 / 4`, última `4 / 4`.
- Las imágenes renderizadas muestran espacio blanco entre la tabla y footer en todas las páginas, sin superposición.

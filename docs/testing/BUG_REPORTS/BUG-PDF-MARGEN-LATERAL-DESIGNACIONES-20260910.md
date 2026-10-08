# Bug: PDF de designaciones pegado a los bordes

## Reproducción

- Vista: `resources/views/designaciones/pdf.blade.php`.
- Mecanismo: `Barryvdh\DomPDF\Facade\Pdf::loadView`.
- Estado inicial: `@page` declaraba `34pt` laterales y el footer declaraba `left/right: 34pt`, pero el PDF real mostraba cabecera, datos e `table.detalle` desde el borde de la hoja.
- Evidencia inicial: `/tmp/opencode/designacion-current.png`.

## Causa

La plantilla no abría el elemento `<body>` y DOMPDF no aplicaba el ancho lateral útil al flujo principal como se esperaba. El footer fijo sí respetaba sus coordenadas independientes.

## Corrección

- Se agregó `<body>` antes de la cabecera.
- Se conservó Carta y el margen `@page` de `10pt 34pt 38pt 34pt`.
- Se estableció `body { margin: 0; padding: 0 34pt; }` para el ancho útil que DOMPDF realmente respeta.
- Se mantuvo `.footer { left: 34pt; right: 34pt; }`.

## Regresión

`php artisan test --filter=DesignacionPdfTest`: 6 pruebas aprobadas, 37 aserciones.

## Verificación visual

PDF final: `/tmp/opencode/designacion-final-34pt.pdf`.

Primera página rasterizada: `/tmp/opencode/designacion-final-34pt.png`.

Resultado: separación lateral simétrica aproximada de 34pt, imágenes dentro del área útil, tabla sin overflow, cabecera centrada y footer alineado con la tabla.

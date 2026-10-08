# BUG-2026-09-10: crear o copiar designacion con contexto vacio

Estado: **RESUELTO EN LA APLICACION**

## Reproduccion

La pantalla de designaciones cargaba el listado, pero el modal de nueva
designacion inicializaba `gestion` y `periodo` vacios. Crear una designacion
vacia quedaba bloqueado por falta de datos; al copiar, esa validacion visual se
omitia y el formulario podia enviarse sin contexto. El servidor rechazaba la
operacion; cuando el usuario completaba un contexto distinto del vigente,
aparecia el mensaje generico de creacion.

## Correccion

El controlador deriva el contexto mas reciente de las designaciones ya cargadas
sin hardcodear gestion ni periodo. El modal usa esos valores al abrirse tanto
para crear vacia como para importar. El servidor tambien completa ambos campos
cuando no se envian, pero conserva la validacion requerida si solo se envia uno.
La validacion JavaScript se ejecuta antes de ambas variantes y bloquea el envio
solo si no existe contexto disponible.

## Regresion y verificacion

- Prueba feature: `test_formulario_de_creacion_inicia_con_el_contexto_mas_reciente`.
- Pruebas de escritura: OK; 17 pruebas, 85 aserciones.
- Pruebas del servicio: OK; 26 pruebas, 168 aserciones.
- `php artisan view:cache --no-ansi`: OK.
- `vendor/bin/pint --test` en archivos modificados: OK.
- Sintaxis PHP de controlador, vista y prueba: OK.
- No se ejecutaron escrituras contra Jachasun durante la reproduccion.

# BUG-2026-10-07: Alpine inicializaba antes de las fábricas de vista

Estado: **RESUELTO**
Severidad: Media
Ambiente: Vistas autenticadas de designaciones

## Precondiciones

La vista usa `x-data` con una fábrica JavaScript cargada desde
`resources/assets/js/`.

## Reproducción

1. Abrir el listado de designaciones en el navegador.
2. Revisar la consola del navegador.

## Resultado esperado y actual

- Esperado: Alpine encuentra las fábricas de cada vista y no reporta errores de
  expresiones; Tailwind se carga localmente sin advertencia de CDN en producción.
- Actual: aparecía `designacionesLista is not defined` y luego errores para las
  propiedades del componente. También aparecía la advertencia de Tailwind CDN.

## Causa raíz

Alpine estaba declarado con `defer` en el `<head>` antes de los scripts de vista,
que se insertaban al final mediante `@stack('scripts')`. Alpine se ejecutaba
primero y evaluaba `x-data` antes de que se registraran las fábricas. Tailwind se
cargaba mediante el script CDN de desarrollo.

## Regresión y corrección

- `FrontendAssetsTest::test_tailwind_es_local_y_alpine_se_carga_despues_de_los_scripts_de_vista`
  verifica que Tailwind no use el CDN y que Alpine aparezca después del stack de
  scripts.
- `FrontendAssetsTest` verifica las tres fábricas `window.*` y los estilos
  generados; `FrontendAssetsServingTest` comprueba sus respuestas MIME y el
  rechazo de rutas externas a `resources/assets/`.
- Alpine ahora se carga después de `@stack('scripts')`. Tailwind se compila a
  `resources/assets/css/tailwind.generated.css` con `npm run build:css`.

## Riesgos y verificación

- Regresión focalizada: 4 pruebas, 80 aserciones, OK.
- Las tres fábricas Alpine se registran en Node; los diez assets responden HTTP
  200 y una ruta ajena a assets responde 404.
- `npm audit`: 0 vulnerabilidades. La suite completa queda bloqueada
  parcialmente por indisponibilidad del entorno de pruebas; ver `STATUS.md`.

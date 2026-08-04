# Reglas permanentes para Codex

## Seguridad

- Nunca conectarse a producción.
- Ejecutar pruebas destructivas únicamente en una base local, testing o staging autorizado.
- No usar datos personales, credenciales reales ni servicios externos reales.

## Reglas de negocio

- No inventar reglas universitarias.
- Registrar cualquier ambigüedad como `NEEDS_BUSINESS_CONFIRMATION`.

## Cambios y pruebas

- No corregir errores sin crear primero una prueba que reproduzca el fallo.
- Ejecutar las pruebas relacionadas después de cada cambio.
- Ejecutar la suite completa antes de cerrar una fase.
- No eliminar, omitir ni debilitar pruebas para conseguir resultados verdes.
- Mantener pruebas independientes, reproducibles y aisladas.

## Fases y documentación

- Detenerse al finalizar cada fase; no iniciar la siguiente sin registrar el resultado.
- Actualizar `docs/testing/STATUS.md` y `docs/testing/TEST_MATRIX.md` al cerrar cada fase.
- Documentar archivos modificados, comandos ejecutados, resultados, fallos y riesgos pendientes.
- Cada bug debe tener un informe en `docs/testing/BUG_REPORTS/` y una prueba de regresión.

## Flujo obligatorio

1. Leer este archivo y el plan de pruebas.
2. Inspeccionar el alcance de la fase.
3. Reproducir los fallos antes de corregirlos.
4. Implementar el cambio mínimo necesario.
5. Ejecutar pruebas específicas y suite relacionada.
6. Actualizar estado, matriz e informes.
7. Detenerse y entregar evidencia de la fase.

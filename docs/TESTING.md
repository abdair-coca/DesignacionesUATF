# Pruebas y trazabilidad

## Comandos de cierre de fase

```bash
composer test -- --env=testing
vendor/bin/pint --test
git diff --check
```

La suite debe ejecutarse con PostgreSQL local/testing y datos sintéticos. No
depende de Jachasun ni de servicios externos reales.

## Cobertura de comportamiento

- acceso, roles y aislamiento entre carreras;
- creación, edición, envío y retiro;
- distribución de horas y grupos obligatorios;
- snapshots y decisiones inmutables;
- aprobación, observación y reenvío;
- importación y previsualización sin escritura;
- notificaciones y propiedad de lectura;
- endpoint institucional `INF` y `UATF` con integración deshabilitada;
- consulta institucional `INF / 0 / 0` para todo el historial del programa;
- pantalla institucional con resultados, consulta vacía y permisos por rol;
- fallos de conexión, permisos y validación.

El detalle vigente está en [`testing/TEST_MATRIX.md`](testing/TEST_MATRIX.md)
y el resultado de cada fase en [`testing/STATUS.md`](testing/STATUS.md).
Los informes históricos se conservan en [`archive/testing/`](archive/testing/).

## Regla de regresión

Antes de corregir un error debe existir una prueba que lo reproduzca. No se
eliminan ni debilitan pruebas para obtener un resultado verde. Los cambios de
lógica requieren una prueba de comportamiento y revisión contra
[`BUSINESS_RULES.md`](BUSINESS_RULES.md).

## Seguridad de datos

Los datasets de testing no contienen datos personales reales. Las migraciones,
seeders y comandos destructivos deben comprobar el ambiente antes de operar.

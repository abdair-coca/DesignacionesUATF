# Plan: Persistir decisiones de Vicerrectorado en Jachasun

## Fases

### Fase 1 — Contrato y función Jachasun (aplicación DBA pendiente)

- [x] Confirmar decisión por asignación docente y estados `APROBADA`/`RECHAZADA`.
- [x] Confirmar observación opcional y guardado válido por fila aunque fallen otras.
- [x] Definir la propuesta SQL en `scripts-bd.sql`, incluida la limpieza de observación vacía.
- [ ] El DBA debe verificar el esquema, aplicar la función y conceder `EXECUTE`.
- [ ] Ejecutar smoke tests autorizados con rollback y confirmar el contrato aplicado.
- Verificación: firma/columnas/permisos aprobados por el administrador de Jachasun.

### Fase 2 — Servicio y autorización

- [x] Implementar la llamada enlazada a las funciones propuestas en el servicio Jachasun.
- [x] Agregar ruta/controller POST con validación y autorización del lado servidor.
- [x] Guardar cada fila en una transacción individual y reportar fallas parciales.
- Verificación: pruebas unitarias/feature con mocks y sin conexión institucional real.

### Fase 3 — Interfaz y verificación

- [x] Enviar decisión y observación opcional desde el modal; cargar decisiones desde Jachasun.
- [x] Mostrar éxito solo para filas guardadas y permitir reintentar las fallidas.
- [ ] Ejecutar smoke autorizado de las funciones después de que el DBA aplique el script.
- Verificación local: suite dirigida, `node --check`, `view:cache` y Pint dirigido.

## Archivos afectados

- `app/Services/Jachasun/JachasunDesignacionesService.php`
- `app/Http/Controllers/` y `routes/web.php`
- `resources/views/vicerrectorado/designaciones/detalle.blade.php`
- `resources/views/Script.js`
- pruebas unitarias y feature de Vicerrectorado
- `docs/specs/changes/decisiones-vicerrectorado-jachasun/scripts-bd.sql` (pendiente DBA)
- `docs/INTEGRATION_JACHASUN.md` y documentación de testing

# Plan: Busqueda dual de docentes en reasignacion

## Fases

### Fase 1 - Servicio y endpoint

- [x] Agregar consultas de catalogo y busqueda global en solo lectura.
- [x] Agregar endpoint protegido para submit search.
- Verificacion: pruebas unitarias y feature del contrato JSON.

### Fase 2 - Modal

- [x] Cargar docentes de la carrera.
- [x] Agregar live search, boton y Enter para busqueda global.
- Verificacion: pruebas de vista y flujo del formulario.

### Fase 3 - Cierre

- [x] Ejecutar pruebas dirigidas y suite completa.
- [x] Actualizar estado y matriz de pruebas.

## Archivos afectados

- `app/Services/Jachasun/JachasunDesignacionesService.php`
- `app/Http/Controllers/DesignacionController.php`
- `routes/web.php`
- `resources/views/designaciones/carrera.blade.php`
- `tests/Unit/JachasunDesignacionesServiceTest.php`
- `tests/Feature/JachasunDesignacionesDetailTest.php`
- `tests/Feature/JachasunDesignacionesEscrituraTest.php`

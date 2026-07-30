# Bitacora - 2026-07-30 - Ajuste de interfaz

## Objetivo del dia

Recuperar la presentacion anterior de las listas principales sin recuperar rutas ni procesos duplicados.

## Que se hizo

- Se restauro el diseno de bandeja para revisiones pendientes, incluyendo carpetas, contadores y buscador.
- La bandeja consulta exclusivamente `propuesta_versiones`; permite ver pendientes, revisadas y todas desde la misma ruta canonica.
- Se restauro la lista visual de Designaciones para Directores y se conectaron sus acciones a `/designaciones`, envio de versiones y retiro de versiones pendientes.
- La opcion visual de copiar crea el borrador vigente y conduce a la importacion segura ya implementada, con el origen seleccionado.
- Se ocultaron acciones antiguas que no existen en el flujo nuevo, como eliminar directamente una propuesta o imprimir desde la lista.

## Decisiones tomadas

- La arquitectura consolidada conserva una sola ruta y una sola logica por accion. Reutilizar una interfaz no equivale a reactivar el sistema heredado.

## Bloqueos / dudas para el supervisor

- Ninguno este dia.

## Proximos pasos

- Validar la experiencia visual con usuarios de Director y Vicerrectorado sobre datos reales.

## Verificacion

- Se compilaron las vistas y se ejecutaron pruebas focalizadas de acceso, roles, importacion, versiones, revisiones y notificaciones.

# Reglas de negocio verificadas

Fuente: código ejecutable, migraciones y pruebas del repositorio. Este
documento no inventa reglas universitarias. Cada regla debe conservar su
separación de estados y su autorización; si una expectativa no está respaldada
por el código, se marca `NEEDS_BUSINESS_CONFIRMATION`.

## Reglas confirmadas

1. Los roles válidos son `director_carrera` y `vicerrectorado`. Un director
   tiene `carrera_id`; Vicerrectorado lo mantiene nulo.
2. Un director solo opera propuestas de su carrera. Vicerrectorado consulta y
   revisa según rol, Policy y estado de versión.
3. Crear y editar exige una gestión `es_actual`; editar exige propuesta
   `borrador` y ninguna versión pendiente.
4. Una fila actual se identifica por propuesta y grupo. La materia debe
   pertenecer a la malla y carrera del grupo de la propuesta.
5. Enviar exige al menos una fila y una fila con docente para cada grupo
   habilitado de la carrera.
6. Una propuesta no puede tener más de una versión pendiente. El número de
   versión es secuencial por propuesta.
7. Enviar crea un snapshot desnormalizado. Snapshots y decisiones son
   inmutables por restricciones/triggers de PostgreSQL.
8. Revisar permite `approve_all` o decisiones por fila. En modo por filas,
   cada fila revisable tiene exactamente una decisión.
9. Una decisión observada exige observación general o de fila. Una decisión
   aprobada no admite observación de fila.
10. Si hay observaciones, la versión queda `observada`. Sin observaciones queda
    `aprobada` y la propuesta pasa a `oficial`.
11. En una revisión mixta, las filas aprobadas pasan a `aprobada_previamente`;
    la aprobación total marca las filas como oficiales.
12. Las horas pagadas y no pagadas son enteros no negativos; las pagadas no
    superan las horas oficiales y la suma cubre las horas oficiales.
13. La importación rechaza fuente y destino con la misma gestión y periodo.
    Previsualizar no escribe; aplicar omite filas `aprobada_previamente`,
    conserva filas exactamente iguales y registra un evento.
14. La importación busca primero propuestas oficiales y usa `designaciones`
    legado para grupos faltantes, filtrando rechazadas y grupos no habilitados.
15. Retirar exige versión `pendiente`, usuario que la envió y carrera propia.
16. Las notificaciones se guardan en `notifications`, se dirigen a
    Vicerrectorado al enviar/retirar y al creador al decidir. Leer exige ser el
    propietario del registro notificable.
17. El login limita cinco intentos por email normalizado/IP durante 60
    segundos y redirige Vicerrectorado a revisiones.
18. La interfaz actual usa Blade, Alpine.js y Tailwind por CDN; no existe un
    flujo de archivos/reportes implementado en la aplicación.

## Transiciones que no se deben combinar

| Ámbito | Estado actual | Efecto documentado |
| --- | --- | --- |
| Propuesta | `borrador` -> `oficial` | Solo tras aprobación total del flujo |
| Versión | `pendiente` -> `retirada` | Retiro autorizado por quien envió |
| Versión | `pendiente` -> `observada` | Requiere corrección y posible reenvío |
| Versión | `pendiente` -> `aprobada` | Revisión sin observaciones |
| Fila | `propuesta` -> `aprobada_previamente` | Aprobación parcial durante corrección |
| Legado | `propuesta/aprobada/rechazada` | Fuente histórica; no es el enum de versiones |

Los snapshots representan una versión enviada y no deben actualizarse para
reflejar una edición posterior. Las decisiones pertenecen a ese snapshot.

## NEEDS_BUSINESS_CONFIRMATION

No convertir estos puntos en reglas hasta recibir confirmación universitaria:

- mínimo, máximo o excepciones de horas; `config/designaciones.php` contradice
  el servicio y las pruebas actuales;
- alcance exacto de Vicerrectorado;
- si se permiten múltiples propuestas para carrera/gestión/periodo y cuál es
  la vigente;
- precedencia y validez operativa de fuente oficial frente a legado;
- significado de `rechazada` legado frente a `observada` y `retirada`;
- obligatoriedad de todos los grupos habilitados y excepciones académicas;
- gestiones/periodos cerrados, concurrencia y reenvío tras observaciones;
- cargas, descargas, reportes y retención;
- deduplicación y reintentos de notificaciones.

## Fuentes verificables

- `app/Services/PropuestaService.php`
- `app/Services/RevisionPropuestaService.php`
- `app/Services/ImportacionPropuestaService.php`
- `app/Policies/PropuestaPolicy.php`
- `app/Policies/PropuestaVersionPolicy.php`
- migraciones de propuestas, versiones, snapshots y decisiones
- pruebas `tests/Feature/Propuesta*Test.php`, `ImportacionPropuestaTest.php`,
  `DesignacionesAceptacionTest.php` y `NotificacionPropuestaTest.php`

La equivalencia visual provisional de `r_estado=SOLICITADO` con `Oficial` en la
lista principal queda marcada como `NEEDS_BUSINESS_CONFIRMATION`.

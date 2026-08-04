# Plan integral de pruebas ejecutado con agentes de código

## 1. Objetivo

Construir una infraestructura de pruebas que permita:

* Descubrir reglas de negocio no documentadas.
* Detectar errores funcionales, de integración y de interfaz.
* Encontrar problemas de concurrencia y duplicación.
* Medir el rendimiento con datos cercanos a producción.
* Comprobar permisos y seguridad.
* Provocar fallos controlados y verificar la recuperación.
* Convertir cada error encontrado en una prueba automatizada.
* Generar un informe final reproducible.

El resultado no debe ser solamente una carpeta llena de tests. Debe producir una respuesta verificable a estas preguntas:

1. ¿Qué partes del sistema funcionan?
2. ¿Qué partes no están suficientemente probadas?
3. ¿Con cuántos usuarios y registros funciona?
4. ¿En qué punto comienza a degradarse?
5. ¿Qué errores pueden corromper o duplicar datos?
6. ¿Qué puede hacer un usuario sin permisos?
7. ¿Cómo se comporta el sistema cuando falla una dependencia?
8. ¿Es seguro desplegar esta versión?

---

# 2. Principios que deben obedecer todos los agentes

## Regla 1: los agentes no pueden probar en producción

Las pruebas deben ejecutarse únicamente contra:

* Una base de datos de testing.
* Un ambiente local.
* Un contenedor aislado.
* Un servidor de staging autorizado.

Debe existir una protección explícita:

```php
if (app()->environment('production')) {
    throw new RuntimeException(
        'Las pruebas destructivas no pueden ejecutarse en producción.'
    );
}
```

También se deben rechazar hosts o bases de datos que no contengan indicadores como:

```text
_testing
_test
_staging
localhost
127.0.0.1
```

## Regla 2: primero reproducir, después corregir

Cuando un agente encuentre un error debe seguir este orden:

1. Documentar el comportamiento esperado.
2. Crear una prueba que reproduzca el error.
3. Ejecutarla y comprobar que falla.
4. Corregir la implementación.
5. Ejecutar nuevamente la prueba.
6. Ejecutar la suite relacionada.
7. Documentar la causa raíz.

Nunca debe modificar código antes de demostrar el fallo.

## Regla 3: los agentes no inventan reglas de negocio

Cuando una regla no esté clara, deben registrarla como:

```text
NEEDS_BUSINESS_CONFIRMATION
```

Ejemplo:

```text
¿Puede un docente superar 40 horas si tiene una autorización especial?
Estado: NEEDS_BUSINESS_CONFIRMATION
```

El agente puede crear una prueba pendiente, pero no debe decidir arbitrariamente cuál es el comportamiento correcto.

## Regla 4: las pruebas no deben depender entre sí

Cada prueba debe:

* Crear sus propios datos.
* Limpiar lo que utiliza.
* Poder ejecutarse individualmente.
* Poder cambiar de orden.
* No depender de datos dejados por otra prueba.

Laravel separa las pruebas unitarias y feature, incluye Pest y PHPUnit y permite usar un entorno `.env.testing`. Su documentación señala que las pruebas feature suelen ofrecer mayor confianza porque prueban la interacción de varias partes o solicitudes HTTP completas.

## Regla 5: los agentes trabajan en ramas separadas

Ejemplo:

```text
test/inventory
test/business-rules
test/backend-feature
test/e2e
test/performance
test/security
test/concurrency
test/chaos
```

Cada agente debe entregar cambios pequeños y revisables.

---

# 3. Arquitectura de agentes

No conviene liberar diez agentes para que modifiquen el repositorio simultáneamente. Deben trabajar bajo un orquestador y con dependencias claras.

## Agente 0: Orquestador de calidad

### Responsabilidad

* Coordinar el trabajo.
* Dividir tareas.
* Evitar cambios cruzados.
* Validar entregables.
* Mantener el tablero de pruebas.
* Detener el proceso ante riesgos.

### No puede

* Inventar requisitos.
* Aprobar una versión con pruebas fallidas.
* ignorar pruebas inestables.
* permitir pruebas destructivas en producción.
* fusionar correcciones sin prueba de regresión.

### Entregables

```text
docs/testing/MASTER_TEST_PLAN.md
docs/testing/TEST_MATRIX.md
docs/testing/RISK_REGISTER.md
docs/testing/FINAL_REPORT.md
```

---

## Agente 1: Cartógrafo del sistema

### Responsabilidad

Comprender el proyecto antes de crear pruebas.

### Debe inspeccionar

* Rutas web y API.
* Controladores.
* Form Requests.
* Policies y Gates.
* Middleware.
* Modelos y relaciones.
* Migraciones.
* Índices y restricciones.
* Jobs y colas.
* Eventos y listeners.
* Commands.
* Servicios externos.
* Componentes React.
* Formularios.
* Roles y permisos.
* Estados de las entidades.
* Importaciones y exportaciones.
* Archivos adjuntos.
* Tareas programadas.

### Entregables

#### `SYSTEM_MAP.md`

Debe contener:

```text
Módulo
├── Entradas
├── Procesamiento
├── Tablas afectadas
├── Salidas
├── Roles permitidos
├── Dependencias
├── Errores posibles
└── Nivel de riesgo
```

#### `ENDPOINT_CATALOG.md`

| Método | Ruta                        | Rol           | Entrada                 | Efecto           | Riesgo  |
| ------ | --------------------------- | ------------- | ----------------------- | ---------------- | ------- |
| POST   | /designaciones              | Administrador | Docente, materia, grupo | Crea designación | Crítico |
| PATCH  | /designaciones/{id}         | Administrador | Datos modificados       | Actualiza        | Alto    |
| POST   | /designaciones/{id}/aprobar | Revisor       | ID                      | Cambia estado    | Crítico |

#### `STATE_TRANSITIONS.md`

Ejemplo:

```text
BORRADOR
  ├── enviar → PENDIENTE
  └── eliminar → ELIMINADA

PENDIENTE
  ├── aprobar → APROBADA
  ├── rechazar → RECHAZADA
  └── devolver → BORRADOR

APROBADA
  └── anular → ANULADA
```

Debe identificar transiciones imposibles, como:

```text
ANULADA → APROBADA
```

---

## Agente 2: Analista de reglas y riesgos

### Responsabilidad

Extraer las reglas de negocio y ordenar las pruebas por impacto.

### Fuentes

* Código.
* Migraciones.
* Validaciones.
* Documentación.
* Historias de usuario.
* Mensajes de interfaz.
* Casos reales proporcionados por usuarios.
* Bugs anteriores.
* Commits relacionados.

### Clasificación de riesgo

#### Crítico

Puede provocar:

* Pérdida de datos.
* Corrupción.
* Duplicación.
* Acceso no autorizado.
* Designaciones incorrectas.
* Aprobación inválida.
* Reportes oficiales incorrectos.

#### Alto

Puede impedir un flujo principal, pero es recuperable.

#### Medio

Afecta experiencia, búsquedas, filtros o casos menos frecuentes.

#### Bajo

Problemas visuales o secundarios sin impacto en datos.

### Entregable: matriz de pruebas

| ID      | Módulo      | Escenario                      | Prioridad | Tipo         | Automatización |
| ------- | ----------- | ------------------------------ | --------- | ------------ | -------------- |
| DES-001 | Designación | Crear designación válida       | Crítica   | Feature      | Sí             |
| DES-002 | Designación | Duplicar docente/materia/grupo | Crítica   | Concurrencia | Sí             |
| DES-003 | Designación | Superar carga máxima           | Crítica   | Regla        | Sí             |
| AUT-001 | Permisos    | Usuario lector crea registro   | Crítica   | Seguridad    | Sí             |
| REP-001 | Reportes    | Exportar 50.000 registros      | Alta      | Rendimiento  | Sí             |

### Técnica para generar escenarios

Por cada operación debe producir:

1. Camino feliz.
2. Datos faltantes.
3. Datos inválidos.
4. Valor mínimo.
5. Valor máximo.
6. Valor superior al máximo.
7. Registro inexistente.
8. Registro eliminado.
9. Estado incorrecto.
10. Usuario sin permisos.
11. Solicitud duplicada.
12. Dos solicitudes simultáneas.
13. Dependencia externa caída.
14. Sesión vencida.
15. Reintento posterior a un timeout.

---

## Agente 3: Ingeniero de datos de prueba

### Responsabilidad

Construir datos realistas y reproducibles.

### Debe implementar

* Factories.
* Estados de factories.
* Seeders pequeños.
* Seeders realistas.
* Seeders de alto volumen.
* Usuarios por rol.
* Datos extremos.
* Datos inconsistentes controlados.
* Archivos de prueba.
* Datos específicos para concurrencia.

Laravel proporciona factories y seeders para crear registros y relaciones de prueba, por lo que esta capa debe apoyarse en ellos en lugar de insertar datos manualmente en cada test.

### Estados sugeridos

```php
Docente::factory()->activo();
Docente::factory()->inactivo();
Docente::factory()->sinCarga();
Docente::factory()->cargaCompleta();
Docente::factory()->conNombreExtenso();

Designacion::factory()->borrador();
Designacion::factory()->pendiente();
Designacion::factory()->aprobada();
Designacion::factory()->rechazada();
Designacion::factory()->anulada();
```

### Perfiles de datos

#### Dataset pequeño

```text
10 docentes
20 materias
50 designaciones
```

Uso: desarrollo rápido.

#### Dataset normal

```text
1.000 docentes
300 materias
10.000 designaciones
```

Uso: integración y E2E.

#### Dataset grande

```text
10.000 docentes
2.000 materias
250.000 designaciones
50.000 documentos
```

Uso: rendimiento, consultas, reportes y paginación.

#### Dataset extremo

Debe incluir:

* Apóstrofes.
* Tildes.
* Ñ.
* Nombres compuestos.
* Unicode.
* Campos en longitud máxima.
* Fechas límite.
* Registros antiguos.
* Relaciones opcionales nulas.
* Documentos grandes.
* Docentes con muchas designaciones.
* Materias sin registros.
* Periodos cerrados.
* Registros eliminados lógicamente.

---

# 4. Fases de ejecución

## Fase 0: protección y preparación

### Objetivo

Crear el entorno seguro antes de ejecutar agentes destructivos.

### Tareas

* Crear `.env.testing`.
* Crear base independiente.
* Configurar almacenamiento temporal.
* Desactivar correos reales.
* Simular APIs externas.
* Desactivar notificaciones reales.
* Proteger producción.
* Crear comando único de ejecución.
* Verificar migraciones desde cero.
* Verificar rollback.
* Guardar versiones de PHP, Node, PostgreSQL y dependencias.

### Criterio de salida

```text
[ ] No existe conexión con producción
[ ] La base de pruebas puede eliminarse y recrearse
[ ] No se envían correos reales
[ ] No se escriben archivos fuera del sandbox
[ ] La aplicación arranca con .env.testing
[ ] Las migraciones funcionan desde una base vacía
```

---

## Fase 1: pruebas estáticas y salud del repositorio

### Agente responsable

Agente de calidad estática.

### Debe ejecutar

Para backend:

```bash
composer validate
php artisan about
php artisan route:list
php artisan migrate:status
php artisan test
```

Cuando estén configurados:

```bash
vendor/bin/pint --test
vendor/bin/phpstan analyse
vendor/bin/pest
```

Para frontend:

```bash
npm ci
npm run lint
npm run typecheck
npm run build
npm test
```

### Debe detectar

* Código muerto.
* Imports no utilizados.
* Rutas duplicadas.
* Errores de tipado.
* Dependencias vulnerables.
* Migraciones conflictivas.
* Variables de entorno ausentes.
* Código que accede directamente a producción.
* Secretos almacenados en el repositorio.

### Criterio de salida

El proyecto se instala y compila desde cero sin correcciones manuales.

---

## Fase 2: pruebas unitarias de dominio

### Objetivo

Probar reglas puras y transiciones.

### Ejemplos

* Cálculo de carga horaria.
* Validación de límites.
* Periodos permitidos.
* Estados de una designación.
* Reglas de aprobación.
* Generación de códigos.
* Cálculos de totales.
* Normalización de datos.

### Regla

No perseguir un porcentaje artificial de cobertura. Priorizar:

* Clases con lógica.
* Reglas críticas.
* Código con muchas condiciones.
* Código modificado frecuentemente.
* Bugs históricos.

### Criterio de salida

Toda regla crítica tiene:

* Al menos un caso válido.
* Al menos un caso inválido.
* Casos límite.
* Mensaje o excepción comprobable.

---

## Fase 3: pruebas feature del backend

### Objetivo

Probar flujos completos dentro de Laravel.

### Cobertura mínima por endpoint

Para cada endpoint crítico:

```text
[ ] Solicitud válida
[ ] Usuario no autenticado
[ ] Rol no autorizado
[ ] Recurso inexistente
[ ] Validación incorrecta
[ ] Relación inexistente
[ ] Estado incompatible
[ ] Operación repetida
[ ] Persistencia correcta
[ ] Respuesta correcta
[ ] Eventos correctos
[ ] Jobs correctos
[ ] Transacción y rollback
```

### Debe utilizar

* `actingAs`.
* Solicitudes JSON o web.
* Assertions de respuesta.
* Assertions de base de datos.
* Fakes de eventos, colas, archivos, correo y notificaciones.
* Transacciones.
* Factories.

Laravel ofrece mecanismos para sustituir o comprobar eventos, servicios y otras dependencias durante las pruebas, evitando ejecutar efectos externos reales.

### Casos esenciales para designaciones

1. Crear una designación válida.
2. Impedir una designación duplicada.
3. Impedir una asignación a un docente inactivo.
4. Impedir el uso de un periodo cerrado.
5. Impedir superar la carga permitida.
6. Comprobar permisos por rol.
7. Aprobar una designación pendiente.
8. Rechazar con motivo obligatorio.
9. Impedir modificar una designación aprobada.
10. Anular y conservar historial.
11. Verificar soft delete.
12. Verificar auditoría.
13. Importar registros válidos.
14. Rechazar importaciones parcialmente inválidas.
15. Generar reportes con filtros.

---

## Fase 4: pruebas de base de datos e integridad

### Objetivo

Demostrar que la base protege los datos incluso cuando la aplicación falla.

### Comprobar

* Foreign keys.
* Unique constraints.
* Índices.
* Campos no nulos.
* Tipos correctos.
* Restricciones de estado.
* Cascadas.
* Soft deletes.
* Transacciones.
* Deadlocks.
* Consultas N+1.
* Consultas lentas.

### Prueba crítica de duplicación

Dos agentes o procesos deben intentar crear simultáneamente:

```text
Mismo docente
Misma materia
Mismo grupo
Mismo periodo
```

El resultado esperado debe ser:

```text
Una sola designación persistida
Una solicitud exitosa
Una solicitud rechazada de manera controlada
Ningún error 500
```

La protección debe existir en la base de datos, no solamente en una consulta previa desde Laravel.

Ejemplo conceptual:

```php
$table->unique([
    'docente_id',
    'materia_id',
    'grupo_id',
    'periodo_id',
]);
```

---

## Fase 5: pruebas del frontend

### Objetivo

Probar componentes y comportamiento de interfaz sin depender inicialmente de todo el backend.

### Debe cubrir

* Estados de carga.
* Estados vacíos.
* Mensajes de error.
* Formularios válidos.
* Formularios inválidos.
* Botones deshabilitados.
* Confirmaciones.
* Tablas.
* Filtros.
* Paginación.
* Modales.
* Errores de API.
* Sesión expirada.
* Reintentos.
* Doble clic.
* Navegación por teclado.
* Diferentes resoluciones.

### Regla

La interfaz no debe:

* Suponer que toda API responde correctamente.
* Mostrar éxito antes de recibir confirmación.
* Permitir múltiples envíos accidentales.
* Ocultar errores.
* Perder silenciosamente lo escrito.
* depender únicamente de permisos visuales.

---

## Fase 6: pruebas end-to-end con Playwright

### Objetivo

Simular a una persona real usando navegador.

Playwright permite pruebas en navegadores diferentes, fixtures aislados, ejecución paralela, generación inicial de pruebas con `codegen`, reintentos controlados, trazas y comparaciones visuales.

### Flujos prioritarios

#### E2E-01: creación completa

1. Iniciar sesión.
2. Abrir designaciones.
3. Buscar docente.
4. Seleccionar materia, grupo y periodo.
5. Guardar.
6. Verificar confirmación.
7. Abrir el registro.
8. Verificar información.

#### E2E-02: aprobación

1. Crear designación como administrador.
2. Cerrar sesión.
3. Iniciar sesión como revisor.
4. Abrir pendientes.
5. Aprobar.
6. Verificar estado.
7. Comprobar que ya no puede editarse.

#### E2E-03: rechazo

* Motivo obligatorio.
* Historial visible.
* Estado correcto.
* Permisos correctos.

#### E2E-04: error de validación

* Enviar formulario incompleto.
* Verificar mensajes.
* Mantener los campos ingresados.
* Corregir y reenviar.

#### E2E-05: doble clic

* Presionar guardar rápidamente varias veces.
* Comprobar un solo registro.

#### E2E-06: sesión expirada

* Abrir formulario.
* Expirar autenticación.
* Intentar guardar.
* Mostrar mensaje.
* No perder datos sin advertencia.

#### E2E-07: dos pestañas

* Abrir el mismo registro dos veces.
* Modificar en la primera.
* Modificar en la segunda.
* Detectar conflicto o aplicar política documentada.

#### E2E-08: exportación

* Aplicar filtros.
* Exportar.
* Validar nombre, tipo y contenido básico.

### Configuración recomendada

Proyectos:

```text
Chromium desktop
Firefox desktop
WebKit desktop
Chromium mobile
```

Artefactos en fallo:

```text
Screenshot
Video
Trace
Logs de consola
Solicitudes de red
```

### Criterio de salida

Los flujos críticos pasan tres veces consecutivas sin depender de reintentos.

Un test que pasa solamente gracias a `retries` debe marcarse como inestable, no como aprobado.

---

## Fase 7: pruebas de concurrencia

### Objetivo

Encontrar errores imposibles de descubrir con un solo usuario.

### Escenarios

1. Dos administradores crean el mismo registro.
2. Dos revisores aprueban simultáneamente.
3. Uno aprueba mientras otro rechaza.
4. Uno elimina mientras otro edita.
5. Importación simultánea del mismo archivo.
6. Job y usuario modifican el mismo registro.
7. Reintento automático después de timeout.
8. Dos pestañas guardan versiones diferentes.
9. Cinco clics rápidos en guardar.
10. Cien solicitudes usan el mismo identificador lógico.

### Resultado requerido

* Sin duplicaciones.
* Sin estados imposibles.
* Sin datos parcialmente escritos.
* Sin respuestas 500 evitables.
* Conflictos registrados.
* Mensajes comprensibles.
* Operaciones idempotentes donde corresponda.

---

## Fase 8: rendimiento y estrés con k6

### Objetivo

Determinar la capacidad real y el punto de degradación.

k6 está diseñado para pruebas de carga y rendimiento, incluyendo smoke, carga promedio, stress, spike, soak y breakpoint. También permite checks, thresholds, escenarios y automatización en CI/CD.

### Paso 1: modelo de uso real

No repetir una sola URL. Crear una distribución aproximada:

```text
40 % consultar designaciones
20 % buscar docentes
10 % consultar detalle
10 % crear designaciones
8 % modificar borradores
5 % revisar pendientes
3 % aprobar o rechazar
3 % generar reportes
1 % importar archivos
```

### Paso 2: perfiles

#### Smoke

```text
1–3 usuarios
1–2 minutos
```

Objetivo: comprobar que el script funciona.

#### Carga normal

```text
25 usuarios
10 minutos
```

Objetivo: uso habitual.

#### Carga alta

```text
50–100 usuarios
15 minutos
```

Objetivo: periodos de mayor actividad.

#### Spike

```text
10 → 150 usuarios en pocos segundos
```

Objetivo: acceso repentino.

#### Stress

```text
Aumentar 25 usuarios cada cinco minutos
```

Objetivo: descubrir degradación.

#### Breakpoint

```text
Continuar hasta superar límites de error o latencia
```

Objetivo: conocer capacidad máxima.

#### Soak

```text
20–40 usuarios durante 2–4 horas
```

Objetivo: detectar acumulación de memoria, conexiones, colas o archivos temporales.

### Umbrales iniciales

Deben ajustarse tras obtener una línea base:

```text
Errores HTTP < 1 %
Checks correctos > 99 %
p95 de consultas comunes < 800 ms
p99 de consultas comunes < 1.500 ms
Sin pérdida de datos
Sin duplicados
Sin saturación permanente de conexiones
```

### Métricas del servidor

Durante la carga registrar:

* CPU.
* RAM.
* Disco.
* Conexiones de PostgreSQL.
* Consultas por segundo.
* Consultas lentas.
* Locks.
* Deadlocks.
* Tamaño de colas.
* Tiempo de jobs.
* Errores 500.
* Reinicios.
* Throughput.
* p50, p90, p95 y p99.

### Regla

El generador de carga no debe ejecutarse en la misma máquina que el sistema cuando se busca medir capacidad real, porque competirían por CPU, memoria y red.

---

## Fase 9: seguridad

### Agente responsable

Agente de seguridad defensiva.

### Base metodológica

Usar la versión estable de OWASP Web Security Testing Guide y seleccionar las pruebas aplicables al sistema. La guía cubre reconocimiento, configuración, identidad, autenticación, autorización, sesiones, validación de entradas, errores, criptografía, lógica de negocio, cliente y API.

### Pruebas mínimas

#### Autenticación

* Credenciales incorrectas.
* Enumeración de usuarios.
* Rate limiting.
* Sesiones antiguas.
* Cierre de sesión.
* Restablecimiento de contraseña.
* Cookies seguras.

#### Autorización

* Cambiar IDs manualmente.
* Consultar registros de otra unidad.
* Crear sin permiso.
* Aprobar con rol incorrecto.
* Descargar reportes restringidos.
* Ejecutar endpoints ocultos en la interfaz.

#### Sesiones

* Sesión expirada.
* Reutilización después de logout.
* Varias sesiones.
* Fijación de sesión.
* CSRF.

#### Entradas

* HTML.
* JavaScript.
* SQL-like strings.
* Unicode extraño.
* Números extremos.
* JSON inesperado.
* Parámetros adicionales.
* Archivos falsos.
* Archivos demasiado grandes.
* Nombres manipulados.

#### Lógica de negocio

* Aprobar algo que no está pendiente.
* Modificar algo aprobado.
* Saltarse pasos.
* Repetir una acción.
* Manipular totales enviados por el frontend.
* Cambiar campos protegidos.
* Forzar periodos cerrados.

### Restricción

El agente no debe realizar ataques contra sistemas ajenos ni producción. Todo se ejecuta en el entorno autorizado.

---

## Fase 10: fallos controlados y resiliencia

### Objetivo

Comprobar cómo responde el sistema cuando sus dependencias dejan de funcionar.

### Escenarios

* PostgreSQL no disponible.
* Consulta con timeout.
* Redis no disponible.
* Worker detenido.
* Correo no disponible.
* Disco sin espacio.
* Archivo eliminado.
* API externa con error 500.
* API externa demasiado lenta.
* Job interrumpido.
* Reinicio del servidor.
* Error durante una transacción.
* Exportación cancelada.
* Caché vaciada.

### Comprobaciones

```text
[ ] No quedan datos parciales
[ ] La transacción hace rollback
[ ] El usuario recibe un error comprensible
[ ] El error queda en logs
[ ] Puede reintentarse
[ ] El reintento no duplica datos
[ ] Las colas se recuperan
[ ] No se revelan trazas sensibles
```

---

## Fase 11: pruebas exploratorias asistidas por agentes

Los agentes automatizados deben generar misiones para personas reales.

Ejemplo:

```text
Necesitas designar a tres docentes.
Uno está inactivo.
Otro ya tiene carga completa.
El tercero es válido.
Después debes corregir una materia y generar un reporte.
```

La persona no debe recibir instrucciones sobre qué botones utilizar.

Registrar:

* Dónde duda.
* Qué intenta hacer.
* Qué mensaje no entiende.
* Qué información no encuentra.
* Cuánto tarda.
* Qué acción inesperada realiza.
* Qué error consigue provocar.

El agente analizará las sesiones y convertirá problemas reproducibles en tickets y pruebas.

---

# 5. Estructura del repositorio

```text
tests/
├── Unit/
│   ├── Domain/
│   ├── Rules/
│   └── Services/
├── Feature/
│   ├── Auth/
│   ├── Designaciones/
│   ├── Docentes/
│   ├── Periodos/
│   ├── Reportes/
│   ├── Imports/
│   └── Permissions/
├── Integration/
│   ├── Database/
│   ├── Queues/
│   └── ExternalServices/
├── Concurrency/
│   └── Designaciones/
├── Security/
│   ├── Authentication/
│   ├── Authorization/
│   ├── InputValidation/
│   └── BusinessLogic/
├── E2E/
│   ├── auth/
│   ├── designaciones/
│   ├── aprobaciones/
│   ├── reportes/
│   └── fixtures/
└── Performance/
    ├── smoke/
    ├── average/
    ├── stress/
    ├── spike/
    ├── soak/
    ├── breakpoint/
    └── helpers/

docs/testing/
├── MASTER_TEST_PLAN.md
├── SYSTEM_MAP.md
├── ENDPOINT_CATALOG.md
├── BUSINESS_RULES.md
├── STATE_TRANSITIONS.md
├── TEST_MATRIX.md
├── RISK_REGISTER.md
├── PERFORMANCE_BASELINE.md
├── SECURITY_REPORT.md
├── BUG_REPORTS/
└── FINAL_REPORT.md
```

---

# 6. Flujo de trabajo de cada agente

Todo agente debe seguir esta secuencia:

```text
1. Leer AGENTS.md.
2. Leer MASTER_TEST_PLAN.md.
3. Inspeccionar solamente el alcance asignado.
4. Identificar riesgos.
5. Actualizar TEST_MATRIX.md.
6. Crear pruebas.
7. Ejecutar las pruebas nuevas.
8. Ejecutar pruebas relacionadas.
9. Guardar resultados.
10. Informar fallos sin ocultarlos.
11. No modificar funcionalidad salvo autorización.
12. Entregar resumen y archivos cambiados.
```

### Formato de informe obligatorio

```markdown
# Informe del agente

## Alcance analizado

## Archivos inspeccionados

## Pruebas creadas

## Resultados

## Errores encontrados

## Riesgos no cubiertos

## Reglas ambiguas

## Cambios realizados

## Comandos ejecutados

## Evidencias

## Recomendación
```

---

# 7. Prompt maestro para el agente orquestador

```text
Actúa como orquestador principal de calidad para este repositorio.

Tu objetivo es coordinar una auditoría funcional, de integración, interfaz,
concurrencia, rendimiento, seguridad y resiliencia del sistema.

REGLAS INNEGOCIABLES:

1. Nunca ejecutes pruebas destructivas contra producción.
2. Verifica el entorno y la base antes de modificar datos.
3. No inventes reglas de negocio.
4. Marca toda ambigüedad como NEEDS_BUSINESS_CONFIRMATION.
5. Antes de corregir un bug, crea una prueba que lo reproduzca y comprueba
   que falla.
6. Cada corrección debe conservar una prueba de regresión.
7. No reduzcas assertions ni desactives tests para conseguir resultados verdes.
8. No ocultes errores, pruebas inestables ni riesgos.
9. No mezcles cambios funcionales con infraestructura de pruebas.
10. Crea commits pequeños y separados por responsabilidad.
11. No utilices datos personales ni credenciales reales.
12. No envíes correos, notificaciones o solicitudes reales.

PRIMERA ETAPA:

- Inspecciona el stack y la estructura del proyecto.
- Identifica backend, frontend, base de datos, colas, servicios externos,
  autenticación, roles, almacenamiento e infraestructura.
- Crea:
  docs/testing/SYSTEM_MAP.md
  docs/testing/ENDPOINT_CATALOG.md
  docs/testing/BUSINESS_RULES.md
  docs/testing/STATE_TRANSITIONS.md
  docs/testing/TEST_MATRIX.md
  docs/testing/RISK_REGISTER.md

SEGUNDA ETAPA:

Divide el trabajo en agentes especializados:

1. Inventario y arquitectura.
2. Reglas de negocio.
3. Datos y factories.
4. Pruebas unitarias.
5. Pruebas feature.
6. Pruebas frontend.
7. Pruebas E2E.
8. Concurrencia e integridad.
9. Rendimiento.
10. Seguridad.
11. Resiliencia.
12. Revisión final.

No permitas que agentes dependientes comiencen antes de recibir sus insumos.
Permite trabajo paralelo solamente cuando no modifiquen los mismos archivos.

PRIORIZACIÓN:

P0:
- Autenticación.
- Permisos.
- Integridad de datos.
- Operaciones principales.
- Aprobaciones.
- Duplicación.
- Pérdida de datos.

P1:
- Reportes.
- Importaciones.
- Búsquedas.
- Filtros.
- Concurrencia.
- Rendimiento normal.

P2:
- Casos extremos.
- Compatibilidad de navegador.
- Rendimiento extremo.
- Aspectos visuales secundarios.

CRITERIO DE ENTREGA:

No declares completado el trabajo hasta generar un informe con:

- Total de pruebas.
- Pruebas aprobadas.
- Pruebas fallidas.
- Pruebas omitidas y motivo.
- Cobertura por módulo.
- Bugs por severidad.
- Riesgos no cubiertos.
- Rendimiento observado.
- Problemas de seguridad.
- Pruebas inestables.
- Ambigüedades de negocio.
- Recomendación de despliegue.
```

---

# 8. Prompt para cada agente especializado

```text
Eres el agente especializado en [ÁREA].

Lee primero:

- AGENTS.md
- docs/testing/MASTER_TEST_PLAN.md
- docs/testing/SYSTEM_MAP.md
- docs/testing/TEST_MATRIX.md
- docs/testing/RISK_REGISTER.md

Tu alcance exacto es:

[ALCANCE]

Debes:

1. Inspeccionar el código relacionado.
2. Identificar caminos felices, inválidos, límites y concurrencia.
3. Crear o actualizar los casos de TEST_MATRIX.md.
4. Escribir pruebas reproducibles e independientes.
5. Ejecutar cada prueba nueva.
6. Demostrar cualquier bug mediante una prueba fallida.
7. No corregir código de producción salvo autorización explícita.
8. Registrar reglas ambiguas como NEEDS_BUSINESS_CONFIRMATION.
9. No conectarte a producción.
10. No desactivar tests existentes.

Al terminar entrega:

- Resumen del módulo.
- Pruebas creadas.
- Comandos ejecutados.
- Resultados.
- Bugs encontrados.
- Evidencias.
- Riesgos restantes.
- Archivos modificados.
- Recomendación.
```

---

# 9. Proceso de tratamiento de bugs

Cada bug debe recibir un archivo:

```text
docs/testing/BUG_REPORTS/BUG-XXX.md
```

Formato:

```markdown
# BUG-XXX: título

## Severidad

## Módulo

## Ambiente

## Precondiciones

## Pasos para reproducir

## Resultado actual

## Resultado esperado

## Evidencia

## Causa raíz

## Prueba de regresión

## Corrección

## Riesgos relacionados

## Estado
```

### Severidades

#### P0 — Bloqueante

* Corrupción o pérdida de datos.
* Acceso administrativo no autorizado.
* Operación principal inutilizable.
* Vulnerabilidad crítica.

#### P1 — Crítico

* Duplicaciones.
* Aprobaciones inválidas.
* Flujo crítico roto.
* Reporte oficial incorrecto.

#### P2 — Importante

* Funcionalidad secundaria defectuosa.
* Rendimiento severamente degradado.
* Error recuperable.

#### P3 — Menor

* Problema visual.
* Mensaje incorrecto.
* Inconsistencia sin impacto funcional.

---

# 10. Integración continua

## En cada pull request

Ejecutar:

```text
Lint
Typecheck
Build
Unit
Feature críticas
Pruebas de permisos
Smoke E2E
Smoke de rendimiento
```

## En la rama principal

Ejecutar:

```text
Suite completa backend
Suite completa frontend
E2E críticos en varios navegadores
Pruebas de concurrencia
Escaneo defensivo de seguridad
```

## Cada noche

Ejecutar:

```text
E2E completo
Carga normal
Pruebas de importación
Pruebas de reportes
Dataset grande
```

## Antes de un release

Ejecutar:

```text
Suite completa
Stress
Spike
Soak
Seguridad
Backup y restauración
Migración desde versión anterior
Rollback de despliegue
Pruebas exploratorias
```

La automatización de rendimiento debe complementarse con ejecuciones manuales y análisis de métricas; la documentación de k6 recomienda integrarla en diferentes puntos del ciclo, como CI, tareas nocturnas y ejecuciones manuales.

---

# 11. Orden exacto recomendado

```text
Semana o ciclo 1
├── Preparar staging
├── Proteger producción
├── Cartografiar sistema
├── Extraer reglas
├── Crear matriz de riesgos
└── Preparar factories

Semana o ciclo 2
├── Unit tests
├── Feature tests críticos
├── Permisos
├── Integridad de base de datos
└── Regresiones conocidas

Semana o ciclo 3
├── Frontend
├── E2E
├── Dos pestañas
├── Doble envío
├── Concurrencia
└── Importaciones y reportes

Semana o ciclo 4
├── Rendimiento
├── Estrés
├── Seguridad
├── Fallos controlados
├── Pruebas con usuarios
└── Informe final
```

Los ciclos no tienen que representar semanas reales. Un agente puede completar varios en una misma sesión, pero no debe saltar las dependencias.

---

# 12. Criterios finales de aprobación

Una versión puede recibir recomendación de despliegue solamente cuando:

```text
[ ] Todos los flujos P0 están automatizados
[ ] Todos los tests P0 pasan
[ ] No existen bugs P0 abiertos
[ ] No existen vulnerabilidades críticas conocidas
[ ] Los roles están probados por backend
[ ] No se producen duplicaciones bajo concurrencia
[ ] Las transacciones evitan escrituras parciales
[ ] Las migraciones funcionan desde cero
[ ] Las migraciones funcionan desde la versión anterior
[ ] El rollback fue ensayado
[ ] El backup fue restaurado correctamente
[ ] Los E2E críticos pasan sin retries
[ ] El rendimiento normal cumple los umbrales
[ ] El sistema se recupera de fallos controlados
[ ] Los logs permiten investigar errores
[ ] Las reglas ambiguas están resueltas o aceptadas
[ ] El informe final está generado
```

---

# 13. Formato del informe final

```markdown
# Informe final de calidad

## 1. Resumen ejecutivo

## 2. Versión evaluada

## 3. Ambiente

## 4. Alcance

## 5. Exclusiones

## 6. Inventario de pruebas

## 7. Resultados por módulo

## 8. Cobertura de reglas

## 9. Bugs encontrados

## 10. Seguridad

## 11. Concurrencia e integridad

## 12. Rendimiento

## 13. Resiliencia

## 14. Pruebas inestables

## 15. Riesgos pendientes

## 16. Recomendación

DECISIÓN:
- APROBADO
- APROBADO CON RIESGOS
- NO APROBADO
```

---

# 14. Estrategia inicial para no abrumar a los agentes

La primera ejecución no debería intentar probar todo. El primer objetivo debe ser una “línea vertical” completa:

```text
Autenticarse
→ crear designación
→ validarla
→ persistirla
→ revisarla
→ aprobarla
→ visualizarla
→ exportarla
```

Esa línea debe tener:

* Unit tests de las reglas.
* Feature tests de la API.
* Restricciones de base de datos.
* E2E en navegador.
* Prueba de permisos.
* Prueba de concurrencia.
* Prueba básica de rendimiento.
* Prueba de fallo y recuperación.

Después se repite el patrón con cada flujo importante.

La meta no es pedirle a un agente “prueba todo el sistema”. La meta es entregarle un alcance verificable, artefactos obligatorios y criterios que le impidan declarar éxito sin evidencias.

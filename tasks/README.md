# Orquestación de tareas — DesignacionesUATF

## Protocolo de comunicación

Yo (orquestador) divido el trabajo en tareas atómicas. El worker IA lee `tasks.md` para contexto global, luego sigue `current.md` para saber qué hacer.

### Flujo
1. Orquestador escribe `tasks/tasks.md` (plan maestro)
2. Orquestador escribe `tasks/current.md` (tarea actual con instrucciones exactas)
3. Worker ejecuta la tarea, modifica solo los archivos indicados
4. Worker escribe `tasks/result.md` con resultado (diff, cambios, problemas)
5. Orquestador revisa, si está bien escribe la siguiente `current.md`
6. Si hay error, orquestador escribe `tasks/fix.md` con instrucciones de corrección

### Reglas
- Worker NO modifica `tasks/tasks.md` ni `tasks/README.md`
- Worker lee `tasks/current.md` al empezar
- Worker escribe `tasks/result.md` al terminar (con comando `git diff` para mostrar cambios)
- Worker nunca deja código a medias — si no puede completar, explicar en `tasks/result.md` qué falta
- Si hay bloqueo, worker escribe `tasks/blocker.md` con descripción + pregunta específica

### Ubicación de archivos
```
tasks/
  README.md     ← este archivo (protocolo)
  tasks.md      ← plan maestro con todas las tareas desglosadas
  current.md    ← tarea activa (instrucciones detalladas)
  result.md     ← resultado de la última tarea (lo escribe worker)
  blocker.md    ← bloqueo/duda (opcional, lo escribe worker)
  fix.md        ← instrucción de corrección (opcional, lo escribe orquestador)
```

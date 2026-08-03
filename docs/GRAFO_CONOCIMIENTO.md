# Grafo de Conocimiento

El grafo del repositorio se genera con Graphify y se almacena en `graphify-out/graph.json`. Su manifiesto incremental esta en `graphify-out/manifest.json`; la cache se omite del control de versiones porque es regenerable.

La generacion actual usa extraccion AST sin servicios externos ni credenciales. Esto incluye archivos, clases, metodos y relaciones estaticas del proyecto.

El grafo incluye tambien `public/boceto-designaciones/app.js`, por lo que sus
funciones de flujo (`switchView`, asignacion, importacion, envio, revision,
validacion de horas y snapshots) pueden localizarse junto con el resto del
repositorio. La documentacion funcional de esas relaciones esta en
[Logica de negocio y flujo completo del boceto](LOGICA_NEGOCIO_Y_FLUJO_BOCETO.md).

## Actualizar

```powershell
graphify update . --force --no-cluster
```

## Consultar

```powershell
graphify query "propuesta version revision" --budget 500
graphify affected "PropuestaService" --depth 2
graphify path "PropuestaController" "RevisionPropuestaService"
```

Despues de modificar el boceto o cualquier modulo del sistema, utiliza el
comando de la seccion **Actualizar**. La salida de Graphify es regenerable y
no forma parte del control de versiones. El indice `.codegraph/` es
independiente y lo mantiene el servidor CodeGraph cuando esta disponible.

Para etiquetar comunidades con analisis semantico se requiere configurar una credencial compatible de Graphify. No es necesaria para mantener el grafo AST actual.

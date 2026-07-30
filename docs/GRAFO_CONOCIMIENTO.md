# Grafo de Conocimiento

El grafo del repositorio se genera con Graphify y se almacena en `graphify-out/graph.json`. Su manifiesto incremental esta en `graphify-out/manifest.json`; la cache se omite del control de versiones porque es regenerable.

La generacion actual usa extraccion AST sin servicios externos ni credenciales. Esto incluye archivos, clases, metodos y relaciones estaticas del proyecto.

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

Para etiquetar comunidades con analisis semantico se requiere configurar una credencial compatible de Graphify. No es necesaria para mantener el grafo AST actual.

# Modelo de datos — Sistema de Designación de Materias a Docentes (UATF)

```mermaid
erDiagram
  CARRERAS ||--o{ MATERIAS : posee
  CARRERAS ||--o{ MALLA_CURRICULAR : incluye
  MATERIAS ||--o{ MALLA_CURRICULAR : aparece_en
  MATERIAS ||--o{ GRUPOS : tiene
  CARRERAS ||--o{ DOCENTES : origen
  DOCENTES ||--o{ DESIGNACIONES : recibe
  MATERIAS ||--o{ DESIGNACIONES : asignada_en
  GRUPOS ||--o{ DESIGNACIONES : asignado_en
  GESTIONES ||--o{ DESIGNACIONES : corresponde_a
  PERIODOS ||--o{ DESIGNACIONES : corresponde_a
  DESIGNACIONES ||--o{ DESIGNACIONES_HISTORIAL : registra

  CARRERAS { int id PK
    string sigla
    string nombre }
  MATERIAS { int id PK
    string sigla
    string nombre
    int carrera_id FK }
  MALLA_CURRICULAR { int id PK
    int carrera_id FK
    int materia_id FK }
  GRUPOS { int id PK
    int materia_id FK
    string codigo
    string estado }
  DOCENTES { int id PK
    string nombre
    string ci
    int carrera_origen_id FK }
  GESTIONES { int id PK
    string nombre }
  PERIODOS { int id PK
    string nombre }
  DESIGNACIONES { int id PK
    int Id_docente FK
    int Id_materia FK
    int Id_grupo FK
    int Id_gestion FK
    int Id_periodo FK
    string estado
    int creado_por
    int aprobado_por }
  DESIGNACIONES_HISTORIAL { int id PK
    int designacion_id FK
    string campo
    string valor_anterior
    string valor_nuevo
    datetime fecha }
```

## Notas del dominio

- Una materia puede pertenecer a la malla curricular de varias carreras (tabla `malla_curricular`
  como tabla puente entre `carreras` y `materias`).
- `gestiones` y `periodos` son catálogos globales, compartidos por toda la universidad (no
  pertenecen a una carrera específica).
- Los `grupos` pertenecen a una materia y se reutilizan entre periodos; tienen un campo `estado`
  (habilitado/deshabilitado) para deshabilitarlos manualmente sin borrarlos.
- `designaciones` usa nombres de columna con mayúscula inicial (`Id_docente`, `Id_materia`,
  `Id_grupo`, `Id_gestion`, `Id_periodo`) para mantener consistencia con el resto del sistema
  existente de la universidad, no con la convención por defecto de Laravel.
- `estado` y `aprobado_por` en `designaciones` están preparados para un futuro flujo de
  aprobación, todavía no definido por el supervisor. No hay lógica de permisos sobre estos
  campos por ahora.
- No hay categoría, dedicación ni especialidad del docente en los datos disponibles.
- `designaciones_historial` registra cambios campo por campo sobre una designación ya creada.

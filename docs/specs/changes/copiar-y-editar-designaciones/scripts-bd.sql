-- Corrección de funciones en BD Jachasun (schema designaciones)
-- Tareas T-01 y T-02 de docs/specs/changes/copiar-y-editar-designaciones/
-- Estado: APLICADO y VERIFICADO por el administrador en la BD real (2026-09-03).
-- Estas definiciones son las que quedaron aplicadas (usuario `utijavier`).

-- ============================================================
-- f_copiar_designacion
--   * detalles: filtrar por id_asignaciones = _id (antes WHERE id = _id)
--   * regla de negocio confirmada: al copiar, id_docente = 898 se guarda como 0
--   * estado = 'SOLICITADO' lo garantiza el DEFAULT de la columna
-- ============================================================
CREATE OR REPLACE FUNCTION designaciones.f_copiar_designacion(
    _id          integer,
    _id_gestion  integer,
    _id_periodo  integer,
    _obs         text
) RETURNS character varying
LANGUAGE plpgsql
AS $function$
DECLARE
    vl_id INTEGER;
BEGIN
    INSERT INTO designaciones.asignaciones(id_programa, id_gestion, id_periodo, obs)
        SELECT id_programa, _id_gestion, _id_periodo, _obs
          FROM designaciones.asignaciones
         WHERE id = _id
        RETURNING id INTO vl_id;

    INSERT INTO designaciones.asignaciones_detalles(id_asignaciones, id_materia, id_docente, id_grupo)
        SELECT vl_id, id_materia, IFF(id_docente = 898, 0, id_docente)::INTEGER, id_grupo
          FROM designaciones.asignaciones_detalles
         WHERE id_asignaciones = _id;

    RETURN 'Copia realizada correctamente';
END;
$function$;

-- ============================================================
-- f_designacion
--   * vl_id = _id al inicio: RETURN QUERY WHERE id = vl_id funciona en
--     INS (vl_id se reasigna con RETURNING), UPD y select (_tipo = '')
--   * estado = 'SOLICITADO' lo garantiza el DEFAULT de la columna
--   * RETURNS TABLE exige los casts (v_facultades_programas.programa es character(3))
-- ============================================================
CREATE OR REPLACE FUNCTION designaciones.f_designacion(
    _id          integer,
    _fecha       character varying,
    _id_programa character varying,
    _id_gestion  integer,
    _id_periodo  integer,
    _obs         text,
    _tipo        character varying
) RETURNS TABLE(
    fecha       timestamp without time zone,
    id_programa character varying,
    programa    character varying,
    id_gestion  integer,
    id_periodo  integer,
    obs         text
)
LANGUAGE plpgsql
AS $function$
DECLARE
    vl_id    INTEGER;
    vl_fecha TIMESTAMP;
BEGIN
    vl_id := _id;
    vl_fecha := IIF(_fecha IS NULL OR _fecha = '', now()::TEXT, _fecha::TEXT)::TIMESTAMP;

    IF _tipo = 'INS' THEN
        INSERT INTO designaciones.asignaciones(fecha, id_programa, id_gestion, id_periodo, obs)
            VALUES (vl_fecha, _id_programa, _id_gestion, _id_periodo, _obs)
            RETURNING id INTO vl_id;
    END IF;

    IF _tipo = 'UPD' THEN
        vl_id := _id;
        UPDATE designaciones.asignaciones
           SET fecha = vl_fecha,
               id_programa = _id_programa,
               id_gestion = _id_gestion,
               id_periodo = _id_periodo,
               obs = _obs
         WHERE id = vl_id;
    END IF;

    RETURN QUERY
        SELECT da.fecha::timestamp,
               da.id_programa::VARCHAR,
               vfp.programa::VARCHAR,
               da.id_gestion::INTEGER,
               da.id_periodo::INTEGER,
               da.obs::TEXT
          FROM designaciones.asignaciones da
               INNER JOIN academico.v_facultades_programas vfp
                       ON vfp.id_programa = da.id_programa
         WHERE id = vl_id;
END;
$function$;

-- ============================================================
-- Smoke tests ejecutados en la BD real (2026-09-03, con rollback):
-- SELECT * FROM designaciones.f_designacion(2131, NULL, '', 0, 0, NULL, '');
--   => 1 fila (prog=INF, gestion=2026)   [antes: 0]
-- SELECT * FROM designaciones.f_designacion(0, '2026-08-31 12:00', 'INF', 2026, 1, 'SMOKE-INS', 'INS');
--   => devuelve la fila nueva con estado=SOLICITADO (default de columna)
-- SELECT * FROM designaciones.f_designacion(2131, '2026-08-31 12:00', 'INF', 2026, 1, 'SMOKE-UPD', 'UPD');
--   => 1 fila actualizada
-- SELECT designaciones.f_copiar_designacion(2131, 9997, 1, 'SMOKE-COPIA');
--   => copia el detalle correcto (docente 602), nueva fila con estado=SOLICITADO
-- ============================================================
-- Contrato propuesto para guardar decisiones por asignación docente en Jachasun.
-- NO EJECUTAR desde la aplicación. Requiere revisión/aplicación del DBA de Jachasun
-- y conceder EXECUTE al usuario de la aplicación antes de habilitar el endpoint.

BEGIN;

ALTER TABLE designaciones.asignaciones_detalles
    ADD COLUMN IF NOT EXISTS estado character varying,
    ADD COLUMN IF NOT EXISTS obs text;

ALTER TABLE designaciones.asignaciones
    ADD COLUMN IF NOT EXISTS obs_vicerrectorado text;

COMMENT ON COLUMN designaciones.asignaciones_detalles.estado IS
    'Estado de revisión por asignación docente: APROBADA o RECHAZADA.';
COMMENT ON COLUMN designaciones.asignaciones_detalles.obs IS
    'Observación de revisión por asignación docente; NULL cuando no se indica.';
COMMENT ON COLUMN designaciones.asignaciones.obs_vicerrectorado IS
    'Observación general de Vicerrectorado; NULL cuando no existe una observación vigente.';

CREATE OR REPLACE FUNCTION designaciones.f_obtener_revision_designacion(
    _id_asignacion integer
)
RETURNS TABLE (
    r_estado character varying,
    r_obs_vicerrectorado text
)
AS
$body$
-- Lista el estado general y la observación vigente de Vicerrectorado.
DECLARE
    vl_id integer;
BEGIN
    vl_id = _id_asignacion;

    IF vl_id IS NULL OR vl_id < 1 THEN
        RAISE EXCEPTION 'Identificador de asignación no válido.';
    END IF;

    RETURN QUERY
    (
        SELECT da.estado::VARCHAR,
               da.obs_vicerrectorado::TEXT
          FROM designaciones.asignaciones da
         WHERE da.id = vl_id
    );
END;
$body$
LANGUAGE 'plpgsql'
VOLATILE
CALLED ON NULL INPUT
SECURITY INVOKER
COST 100
ROWS 1;

ALTER FUNCTION designaciones.f_obtener_revision_designacion(integer)
OWNER TO utijavier;

CREATE OR REPLACE FUNCTION designaciones.f_guardar_revision_designacion(
    _id_asignacion integer,
    _estado character varying,
    _obs_vicerrectorado text
)
RETURNS TABLE (
    r_estado character varying,
    r_obs_vicerrectorado text
)
AS
$body$
-- Guarda una decisión general sin modificar los estados de las filas.
DECLARE
    vl_id integer;
    vl_estado character varying;
    vl_obs text;
BEGIN
    vl_id = _id_asignacion;
    vl_estado = UPPER(BTRIM(_estado));
    vl_obs = NULLIF(BTRIM(COALESCE(_obs_vicerrectorado, '')), '');

    IF vl_id IS NULL OR vl_id < 1 THEN
        RAISE EXCEPTION 'Identificador de asignación no válido.';
    END IF;

    IF vl_estado IS NULL OR vl_estado NOT IN ('APROBADO', 'OBSERVADA') THEN
        RAISE EXCEPTION 'Estado general no válido.';
    END IF;

    IF char_length(coalesce(vl_obs, '')) > 1000 THEN
        RAISE EXCEPTION 'La observación excede el máximo permitido.';
    END IF;

    IF vl_estado = 'OBSERVADA' AND vl_obs IS NULL THEN
        RAISE EXCEPTION 'La observación es obligatoria.';
    END IF;

    IF vl_estado = 'APROBADO' THEN
        vl_obs = NULL;
    END IF;

    UPDATE designaciones.asignaciones da
       SET estado = vl_estado,
           obs_vicerrectorado = vl_obs
     WHERE da.id = vl_id;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'La designación indicada no existe.';
    END IF;

    RETURN QUERY
    (
        SELECT da.estado::VARCHAR,
               da.obs_vicerrectorado::TEXT
          FROM designaciones.asignaciones da
         WHERE da.id = vl_id
    );
END;
$body$
LANGUAGE 'plpgsql'
VOLATILE
CALLED ON NULL INPUT
SECURITY INVOKER
COST 100
ROWS 1;

ALTER FUNCTION designaciones.f_guardar_revision_designacion(
    integer, character varying, text
)
OWNER TO utijavier;

CREATE OR REPLACE FUNCTION designaciones.f_listar_decisiones_asignacion_detalle(
    _id_asignacion integer
)
RETURNS TABLE (
    r_id_detalle integer,
    r_estado character varying,
    r_obs text
)
AS
$body$
-- Lista el estado y la observación de cada detalle de una designación.
-- El detalle académico se consulta mediante f_asignaciones_detalles.
DECLARE
    vl_id integer;
BEGIN
    vl_id = _id_asignacion;

    IF vl_id IS NULL OR vl_id < 1 THEN
        RAISE EXCEPTION 'Identificador de asignación no válido.';
    END IF;

    RETURN QUERY
    (
        SELECT dad.id::INTEGER,
               dad.estado::VARCHAR,
               dad.obs::TEXT
          FROM designaciones.asignaciones_detalles dad
         WHERE dad.id_asignaciones = vl_id
         ORDER BY dad.id
    );
END;
$body$
LANGUAGE 'plpgsql'
VOLATILE
CALLED ON NULL INPUT
SECURITY INVOKER
COST 100
ROWS 1000;

ALTER FUNCTION designaciones.f_listar_decisiones_asignacion_detalle (
    _id_asignacion integer
)
OWNER TO utijavier;

CREATE OR REPLACE FUNCTION designaciones.f_guardar_decision_asignacion_detalle(
    _id_asignacion integer,
    _id_detalle integer,
    _estado character varying,
    _obs text
)
RETURNS TABLE (
    r_id_detalle integer,
    r_estado character varying,
    r_obs text
)
AS
$body$
-- Guarda el estado y la observación de un detalle docente.
DECLARE
    vl_id                 INTEGER;
    vl_estado             VARCHAR;
    vl_obs                TEXT;
    vl_total              INTEGER;
    vl_pendientes         INTEGER;
    vl_aprobadas          INTEGER;
    vl_estado_designacion VARCHAR;
BEGIN
    vl_id = _id_detalle;
    vl_estado = UPPER(BTRIM(_estado));
    vl_obs = NULLIF(BTRIM(COALESCE(_obs, '')), '');

    IF _id_asignacion IS NULL OR _id_asignacion < 1
       OR _id_detalle IS NULL OR _id_detalle < 1 THEN
        RAISE EXCEPTION 'Identificador de asignación no válido.';
    END IF;

    IF vl_estado IS NULL OR vl_estado NOT IN ('APROBADA', 'RECHAZADA') THEN
        RAISE EXCEPTION 'Estado de decisión no válido.';
    END IF;

    IF char_length(coalesce(vl_obs, '')) > 1000 THEN
        RAISE EXCEPTION 'La observación excede el máximo permitido.';
    END IF;

    PERFORM 1
      FROM designaciones.asignaciones da
     WHERE da.id = _id_asignacion
     FOR UPDATE;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'La designación indicada no existe.';
    END IF;

    UPDATE designaciones.asignaciones_detalles dad
       SET estado = vl_estado,
           obs = vl_obs
     WHERE dad.id = vl_id
       AND dad.id_asignaciones = _id_asignacion;

    IF NOT FOUND THEN
        RAISE EXCEPTION 'La asignación no pertenece a la designación indicada.';
    END IF;

    SELECT COUNT(*)::INTEGER,
           COUNT(*) FILTER (
               WHERE UPPER(BTRIM(COALESCE(dad.estado, ''))) NOT IN ('APROBADA', 'RECHAZADA')
           )::INTEGER,
           COUNT(*) FILTER (
               WHERE UPPER(BTRIM(COALESCE(dad.estado, ''))) = 'APROBADA'
           )::INTEGER
      INTO vl_total, vl_pendientes, vl_aprobadas
      FROM designaciones.asignaciones_detalles dad
     WHERE dad.id_asignaciones = _id_asignacion;

    IF vl_total = 0 OR vl_pendientes > 0 THEN
        vl_estado_designacion = 'SOLICITADO';
    ELSIF vl_aprobadas = vl_total THEN
        vl_estado_designacion = 'APROBADO';
    ELSE
        vl_estado_designacion = 'OBSERVADA';
    END IF;

    UPDATE designaciones.asignaciones da
       SET estado = vl_estado_designacion,
           obs_vicerrectorado = CASE
               WHEN vl_estado_designacion = 'APROBADO' THEN NULL
               ELSE da.obs_vicerrectorado
           END
     WHERE da.id = _id_asignacion;

    RETURN QUERY
    (
        SELECT dad.id::INTEGER,
               dad.estado::VARCHAR,
               dad.obs::TEXT
          FROM designaciones.asignaciones_detalles dad
         WHERE dad.id = vl_id
           AND dad.id_asignaciones = _id_asignacion
    );
END;
$body$
LANGUAGE 'plpgsql'
VOLATILE
CALLED ON NULL INPUT
SECURITY INVOKER
COST 100
ROWS 1;

ALTER FUNCTION designaciones.f_guardar_decision_asignacion_detalle (
    _id_asignacion integer,
    _id_detalle integer,
    _estado character varying,
    _obs text
)
OWNER TO utijavier;

REVOKE ALL ON FUNCTION designaciones.f_listar_decisiones_asignacion_detalle(integer) FROM PUBLIC;
REVOKE ALL ON FUNCTION designaciones.f_guardar_decision_asignacion_detalle(integer, integer, character varying, text) FROM PUBLIC;
REVOKE ALL ON FUNCTION designaciones.f_obtener_revision_designacion(integer) FROM PUBLIC;
REVOKE ALL ON FUNCTION designaciones.f_guardar_revision_designacion(integer, character varying, text) FROM PUBLIC;
GRANT EXECUTE ON FUNCTION designaciones.f_listar_decisiones_asignacion_detalle(integer) TO usr_designaciones;
GRANT EXECUTE ON FUNCTION designaciones.f_guardar_decision_asignacion_detalle(integer, integer, character varying, text) TO usr_designaciones;
GRANT EXECUTE ON FUNCTION designaciones.f_obtener_revision_designacion(integer) TO usr_designaciones;
GRANT EXECUTE ON FUNCTION designaciones.f_guardar_revision_designacion(integer, character varying, text) TO usr_designaciones;

COMMIT;

-- Verificación en una BD autorizada con datos sintéticos:
-- BEGIN;
-- SELECT * FROM designaciones.f_listar_decisiones_asignacion_detalle(<id_asignacion>);
-- SELECT * FROM designaciones.f_guardar_decision_asignacion_detalle(<id_asignacion>, <id_detalle>, 'APROBADA', NULL);
-- SELECT * FROM designaciones.f_guardar_decision_asignacion_detalle(<id_asignacion>, <id_detalle>, 'RECHAZADA', 'Motivo de prueba');
-- ROLLBACK;

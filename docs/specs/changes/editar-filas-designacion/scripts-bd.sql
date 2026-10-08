-- Corrección de función en BD Jachasun (schema designaciones)
-- Feature: editar filas de detalle de una designación
-- docs/specs/changes/editar-filas-designacion/
-- Estado: APLICADA y VERIFICADA en la BD real (2026-09-04) por el administrador.
-- La versión aplicada difiere de la propuesta original en el RETURNS TABLE
-- (14 columnas, incluye r_id_detalle, r_id_gestion, r_id_periodo, r_id_programa,
-- r_programa y r_docente en lugar de r_ci/r_nombres/r_sigla) y devuelve SOLO la
-- fila afectada (WHERE dad.id = vl_id_detalle), sin filtro NOT IN (0, 898).
-- La corrección clave fue calificar la vista: `academico.v_facultades_programas`
-- (sin `academico.` la función fallaba con 42P01 para el usuario de la app,
-- cuyo search_path no incluye el schema academico).

-- ============================================================
-- f_designacion_detalle
--   * _id_detalle = 0  -> INSERT de una fila nueva para la designación _id
--   * _id_detalle > 0  -> UPDATE de la fila existente
--   * El RETURN QUERY devuelve la fila afectada (1 fila).
--   * Corrige los errores de la versión original: id_docente duplicado en UPDATE,
--     columnas hrs_teoria/hrs_practica inexistentes, JOIN da.id_asignaciones=da.id
--     inválido, 13 columnas vs 12 en RETURNS TABLE, WHERE id ambiguo.
-- ============================================================
CREATE OR REPLACE FUNCTION designaciones.f_designacion_detalle(
    _id integer, _id_detalle integer, _id_docente integer, _id_materia integer, _id_grupo integer,
    _hrs_teoria integer, _hrs_practica integer, _hrs_laboratorio integer
)
 RETURNS TABLE(r_id integer, r_id_detalle integer, r_id_gestion integer, r_id_periodo integer,
               r_id_materia integer, r_materia character varying, r_id_docente integer,
               r_docente character varying, r_id_grupo integer, r_hrs_teoricas integer,
               r_hrs_practicas integer, r_hrs_laboratorio integer, r_id_programa character varying,
               r_programa character varying)
 LANGUAGE plpgsql
AS $function$
DECLARE
		vl_id			INTEGER;
        vl_id_detalle	INTEGER;
        vl_fecha		TIMESTAMP;

BEGIN
		vl_id_detalle = _id_detalle;
		SELECT id_asignaciones INTO vl_id FROM designaciones.asignaciones_detalles WHERE id = _id_detalle;
        IF FOUND AND _id != 0 THEN
           -- El registro existe es para actualizar
           UPDATE designaciones.asignaciones_detalles SET id_materia   = _id_materia,
												  id_docente   = _id_docente,
                                                          id_grupo     = _id_grupo,
                                                          hrs_teoricas = _hrs_teoria,
                                                          hrs_practicas= _hrs_practica,
                                                          hrs_laboratorio = _hrs_laboratorio,
                                                          fecha = now()
            WHERE id = _id_detalle;
        ELSE
	IF _id_detalle = 0 THEN
               -- Insertar Registro
               INSERT INTO designaciones.asignaciones_detalles(id_asignaciones, id_materia, id_docente, id_grupo, hrs_teoricas, hrs_practicas, hrs_laboratorio)
										    VALUES(_id, _id_materia, _id_docente, _id_grupo, _hrs_teoria, _hrs_practica, _hrs_laboratorio)
	   RETURNING id INTO vl_id_detalle;
             END IF;
        END IF;

        RETURN query(
			SELECT da.id::INTEGER,
	   dad.id::INTEGER as id_detalle,
	   da.id_gestion::INTEGER,
                           da.id_periodo::INTEGER,
                           dad.id_materia::INTEGER,
                           pm.materia::VARCHAR,
                           dad.id_docente::INTEGER,
                           (doo.paterno || ' ' || doo.materno || ', ' || doo.nombres)::VARCHAR,
                           dad.id_grupo::INTEGER,
                           dad.hrs_teoricas::INTEGER,
                           dad.hrs_practicas::INTEGER,
                           dad.hrs_laboratorio::INTEGER,
	   doo.id_programa::VARCHAR,
                           vfp.programa::VARCHAR
                      FROM designaciones.asignaciones da
			INNER JOIN designaciones.asignaciones_detalles dad
		ON dad.id_asignaciones = da.id
                                INNER JOIN academico.docentes doo
		ON doo.id_docente = dad.id_docente
                                INNER JOIN academico.pln_materias pm
		ON pm.id_materia = dad.id_materia
                                INNER JOIN academico.v_facultades_programas vfp
		ON vfp.id_programa = doo.id_programa
                     WHERE dad.id = vl_id_detalle
        );
END;
$function$;

-- ============================================================
-- Smoke tests ejecutados en la BD real (2026-09-04, con rollback, conexión app):
--   UPDATE: SELECT * FROM designaciones.f_designacion_detalle(2138, 80360, 672, 3868, 5, 10, 2, 1);
--     => OK, 1 fila (det=80360, doc=672, r_docente=CONDORI LLANOS MARIBEL ROSARIO, hT=10, prog=INF);
--        tras rollback el detalle 80360 queda intacto (doc=591, hT=0).
--   INSERT: SELECT * FROM designaciones.f_designacion_detalle(2138, 0, 602, 9595, 3, 4, 2, 0);
--     => OK, 1 fila nueva (doc=602, mat=9595, grp=3, hT=4, hP=2, hL=0); tras rollback no queda fila.
-- ============================================================

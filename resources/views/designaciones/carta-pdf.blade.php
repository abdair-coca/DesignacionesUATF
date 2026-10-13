<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Designación docente</title>
    <style>
        @page { margin: 22mm 18mm 24mm; }
        * { box-sizing: border-box; }
        body { color: #111; font-family: Arial, sans-serif; font-size: 10.5pt; line-height: 1.27; margin: 0; }
        .review-note { font-size: 8pt; margin: -11mm 0 11mm; text-align: right; }
        .letter-date, .faculty-cite { color: #111; }
        .letter-date { margin-bottom: 2px; }
        .faculty-cite { margin-bottom: 27px; }
        .recipient { margin-bottom: 0; }
        .present { margin-top: 0; }
        .greeting { margin: 32px 0 8px; }
        .body-copy { margin: 0 0 8px; text-align: justify; }
        .courses { border-collapse: collapse; margin: 12px auto 14px; width: 72%; }
        .courses td { padding: 0 5px; vertical-align: top; }
        .courses .hours { text-align: left; white-space: nowrap; width: 24%; }
        .courses .total td { padding-top: 3px; }
        .closing { margin: 10px 0 0; }
        .signatures { margin-top: 80px; }
        .signatures table { border-collapse: collapse; width: 100%; }
        .signatures td { text-align: center; vertical-align: bottom; width: 42%; }
        .signatures .approval { padding-bottom: 17px; width: 16%; }
        .signer-name, .signer-role { display: block; }
        .signer-role { font-size: 8.5pt; font-weight: bold; }
        .copies { bottom: 7mm; left: 0; position: fixed; }
        .page-number { bottom: 7mm; position: fixed; right: 0; }
    </style>
</head>
<body>
    @if(($carta['leyenda_revision'] ?? '') !== '')
        <div class="review-note">{{ $carta['leyenda_revision'] }}</div>
    @endif

    <p class="letter-date">Potosí, {{ $carta['fecha_resolucion_texto'] }}</p>
    <p class="faculty-cite">N° de cite de la Facultad {{ $carta['numero_cite'] }}</p>

    <p class="recipient">Señor/a {{ $carta['grado_academico'] }}</p>
    <p class="recipient">{{ $carta['docente_nombre'] }}</p>
    <p class="present">Presente</p>

    <p class="greeting">De nuestra mayor distinción:</p>
    @if(($carta['condicion'] ?? '') === 'DOCENTE ORDINARIO CONTRATADO')
        <p class="body-copy">
            Por Resolución del Consejo Facultativo de la Facultad de {{ $carta['facultad'] }}, N° {{ $carta['numero_resolucion'] }}/{{ $carta['fecha_resolucion_anio'] }}, nos permitimos comunicarle que en su condición de
        <strong>DOCENTE ORDINARIO CONTRATADO</strong> a {{ $carta['modalidad'] }} de la Carrera de {{ $carrera->nombre }}, en concordancia con el Artículo 19 del Reglamento del Régimen Académico Docente de la Universidad Boliviana, ha sido designado para la gestión académica {{ $asignacion['gestion'] }}@if($carta['carrera_semestralizada']) (Semestre I/{{ $asignacion['gestion'] }} y Semestre II/{{ $asignacion['gestion'] }})@endif, con las siguientes asignaturas:
        </p>
    @elseif(($carta['condicion'] ?? '') === 'DOCENTE EXTRAORDINARIO')
        <p class="body-copy">
            Por Resolución del Consejo Facultativo de la Facultad de {{ $carta['facultad'] }}, N° {{ $carta['numero_resolucion'] }}/{{ $carta['fecha_resolucion_anio'] }}, nos permitimos comunicarle que en su condición de
        <strong>DOCENTE EXTRAORDINARIO</strong> a {{ $carta['modalidad'] }} de la Carrera de {{ $carrera->nombre }}, en concordancia con el Artículo 11 del Reglamento del Régimen Académico Docente de la Universidad Boliviana, ha sido designado docente por la gestión {{ $asignacion['gestion'] }}@if($carta['carrera_semestralizada']) (Semestre I/{{ $asignacion['gestion'] }} y Semestre II/{{ $asignacion['gestion'] }})@endif, con las siguientes asignaturas:
        </p>
    @else
    <p class="body-copy">
        Por Resolución del Consejo Facultativo de la Facultad de {{ $carta['facultad'] }}, N° {{ $carta['numero_resolucion'] }}/{{ $carta['fecha_resolucion_anio'] }}, nos permitimos comunicarle que en su condición de
        <strong>DOCENTE ORDINARIO TITULAR</strong> a {{ $carta['modalidad'] }} de la Carrera de {{ $carrera->nombre }}, en concordancia con el Artículo 20 del Reglamento del Régimen Académico Docente de la Universidad Boliviana, ha sido designado docente por la gestión académica {{ $asignacion['gestion'] }}@if($carta['carrera_semestralizada']) (Semestre I/{{ $asignacion['gestion'] }} y Semestre II/{{ $asignacion['gestion'] }})@endif, teniendo a su cargo las siguientes asignaturas:
    </p>
    @endif

    <table class="courses">
        <tbody>
            @foreach($filas as $fila)
                <tr>
                    <td>{{ $fila['materia_sigla'] }}@if(($fila['grupo_texto'] ?? '') !== '') {{ $fila['grupo_texto'] }}@endif {{ $fila['materia_nombre'] }}</td>
                    <td class="hours">{{ $fila['horas_total'] }} Hrs.</td>
                </tr>
            @endforeach
            <tr class="total">
                <td>TOTAL</td>
                <td class="hours">{{ $filas->sum('horas_total') }} Hrs.</td>
            </tr>
        </tbody>
    </table>

    <p class="body-copy">
        Debiendo iniciar sus actividades a partir del {{ $carta['fecha_inicio_texto'] }}. Para coordinar horarios y otros aspectos inherentes a sus funciones académicas, se le solicita pasar por la Dirección de Carrera.
    </p>
    <p class="body-copy">Con este grato motivo, saludamos a usted con las consideraciones más distinguidas.</p>
    <p class="closing">Atentamente,</p>

    <div class="signatures">
        <table>
            <tbody>
                <tr>
                    <td>
                        <span class="signer-name">{{ $carta['decan_nombre'] }}</span>
                        <span class="signer-role">{{ $carta['decan_cargo'] }}</span>
                    </td>
                    <td class="approval">VoBo</td>
                    <td>
                        <span class="signer-name">{{ $carta['vicerrector_nombre'] }}</span>
                        <span class="signer-role">{{ $carta['vicerrector_cargo'] }}</span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    <div class="copies">Copia: VRC, DPER, ARCH. FAC.</div>
    <div class="page-number">1</div>
</body>
</html>

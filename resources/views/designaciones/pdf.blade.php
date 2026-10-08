<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">

    <title>Reporte de designaciones — {{ $carrera->sigla }}</title>

    <style>
        <?php echo file_get_contents(resource_path('assets/css/designaciones/pdf.css')); ?>



    </style>
</head>


<body class="designaciones-pdf">


    <!-- =========================================================
         CABECERA
         ========================================================= -->

    <div class="header">

        <table class="header-top">
            <tr>

                <td class="header-left">

                    <img
                        src="{{ public_path('imgs/resources/LogoDC.png') }}"
                        alt=""
                    >

                </td>


                <td class="header-center">

                    <div class="brand">

                        <div class="brand-data-center">
                            DATA CENTER
                        </div>


                        <div class="brand-university">
                            UNIVERSIDAD AUT&Oacute;NOMA TOM&Aacute;S FR&Iacute;AS
                        </div>


                        <table class="line-table">
                            <tr>

                                <td class="line-dot-cell">
                                    <span class="blue-dot"></span>
                                </td>

                                <td>
                                    <div class="blue-line"></div>
                                </td>

                                <td class="line-dot-cell">
                                    <span class="blue-dot right"></span>
                                </td>

                            </tr>
                        </table>



                        <div class="document-title">

                            <div class="document-title-main">
                                SEGUIMIENTO ACAD&Eacute;MICO
                            </div>


                            <div class="document-title-secondary">
                                REPORTE DE DESIGNACIONES
                            </div>

                        </div>



                        <table class="line-table-bottom">
                            <tr>

                                <td class="line-dot-cell">
                                    <span class="blue-dot"></span>
                                </td>

                                <td>
                                    <div class="blue-line"></div>
                                </td>

                                <td class="line-dot-cell">
                                    <span class="blue-dot right"></span>
                                </td>

                            </tr>
                        </table>

                    </div>

                </td>



                <td class="header-right">

                    <img
                        src="{{ public_path('imgs/resources/LogoDCizq.png') }}"
                        alt=""
                    >

                </td>

            </tr>
        </table>



        <div class="header-data">

            <table class="header-data-table">

                <tr>
                    <td class="data-label">
                        CARRERA:
                    </td>

                    <td class="data-value">
                        {{ $carrera->nombre }}
                    </td>
                </tr>


                <tr>
                    <td class="data-label">
                        DESCRIPCI&Oacute;N:
                    </td>

                    <td class="data-value">
                        {{ $asignacion['detalle'] ?? '-' }}
                    </td>
                </tr>


                <tr>
                    <td class="data-label">
                        GESTI&Oacute;N:
                    </td>

                    <td class="data-value">
                        {{ $asignacion['gestion'] ?? '-' }}
                    </td>
                </tr>


                <tr>
                    <td class="data-label">
                        PERIODO:
                    </td>

                    <td class="data-value">
                        {{ $asignacion['periodo'] ?? '-' }}
                    </td>
                </tr>

            </table>

        </div>

    </div>



    <div class="header-spacing"></div>



    <!-- =========================================================
         TABLA
         ========================================================= -->

    <div class="detalle-wrapper">

        <table class="detalle">


            <thead>


                <!--
                ====================================================
                ESPACIO SUPERIOR REPETIBLE

                Esta fila se repite automáticamente en página 2,
                página 3, página 4...

                Altura: 28.35pt ≈ 1 cm
                ====================================================
                -->

                <tr class="page-top-spacing">

                    <th colspan="7">

                        <div class="page-top-spacing-content"></div>

                    </th>

                </tr>



                <!-- =================================================
                     CABECERA REAL
                     ================================================= -->

                <tr>

                    <th class="col-docente">
                        DOCENTE
                    </th>

                    <th class="col-ci">
                        CI
                    </th>

                    <th class="col-materia">
                        MATERIA
                    </th>

                    <th class="col-grupo">
                        GRUPO
                    </th>

                    <th class="col-horas">
                        TE&Oacute;RICAS
                    </th>

                    <th class="col-horas">
                        PR&Aacute;CTICAS
                    </th>

                    <th class="col-horas">
                        LABORATORIO
                    </th>

                </tr>

            </thead>



            <tbody>

                @forelse($filas as $fila)

                    <tr>

                        <td>
                            {{ $fila['docente_nombre'] ?? '-' }}
                        </td>


                        <td class="centro">
                            {{ $fila['ci'] ?? '-' }}
                        </td>


                        <td>

                            {{ $fila['materia_sigla'] ?? '-' }}

                            &mdash;

                            {{ $fila['materia_nombre'] ?? '-' }}

                        </td>


                        <td class="centro">
                            {{ $fila['grupo_id'] ?? '-' }}
                        </td>


                        <td class="num">
                            {{ $fila['horas_teoricas'] ?? '-' }}
                        </td>


                        <td class="num">
                            {{ $fila['horas_practicas'] ?? '-' }}
                        </td>


                        <td class="num">
                            {{ $fila['horas_laboratorio'] ?? '-' }}
                        </td>

                    </tr>


                @empty


                    <tr>

                        <td
                            colspan="7"
                            class="centro pdf-cell-padded"
                        >

                            No se recibieron filas de detalle.

                        </td>

                    </tr>


                @endforelse

            </tbody>

        </table>

    </div>



    <!-- =========================================================
         FOOTER
         ========================================================= -->

    <div class="footer">


        <table class="footer-table">

            <tr>

                <td class="footer-left">

                    Carrera: {{ $carrera->nombre }}

                </td>


                <td class="footer-center">

                    &nbsp;

                </td>


                <td class="footer-right">

                    FECHA DE IMPRESI&Oacute;N:
                    {{ $fechaImpresion }}

                </td>

            </tr>

        </table>



        <table class="footer-blue">

            <tr>

                <td class="footer-blue-left"></td>

                <td class="footer-blue-gap"></td>

                <td class="footer-blue-right"></td>

            </tr>

        </table>



        <div class="footer-red"></div>


    </div>


</body>
</html>

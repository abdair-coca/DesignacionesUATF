<?php

namespace Database\Seeders;

use App\Models\Carrera;
use App\Models\Materia;
use Illuminate\Database\Seeder;

class MateriaSeeder extends Seeder
{
    /**
     * Materias de especialidad por carrera (además de las generales que se agregan abajo).
     */
    private const ESPECIALIDADES = [
        'CIV' => ['Mecánica de Materiales', 'Hidráulica', 'Estructuras I', 'Topografía', 'Diseño de Puentes'],
        'INF' => ['Estructuras de Datos', 'Base de Datos I', 'Ingeniería de Software I', 'Redes de Computadoras', 'Sistemas Operativos'],
        'IND' => ['Investigación de Operaciones', 'Gestión de la Calidad', 'Ingeniería de Métodos', 'Logística Industrial'],
        'ELE' => ['Circuitos Eléctricos I', 'Máquinas Eléctricas', 'Sistemas de Potencia', 'Instalaciones Eléctricas'],
        'ELT' => ['Circuitos Electrónicos I', 'Sistemas Digitales', 'Telecomunicaciones I', 'Procesamiento de Señales'],
        'MEC' => ['Mecánica de Fluidos', 'Termodinámica', 'Diseño Mecánico I', 'Resistencia de Materiales'],
        'QUI' => ['Química Orgánica', 'Fisicoquímica', 'Procesos Industriales', 'Operaciones Unitarias'],
        'MIN' => ['Geología General', 'Laboreo de Minas', 'Ventilación de Minas', 'Mecánica de Rocas'],
        'ARQ' => ['Taller de Diseño I', 'Historia de la Arquitectura', 'Construcción I', 'Urbanismo I'],
        'MED' => ['Anatomía I', 'Fisiología Humana', 'Bioquímica Médica', 'Patología General'],
        'ODO' => ['Anatomía Dental', 'Operatoria Dental I', 'Periodoncia', 'Endodoncia'],
        'ENF' => ['Fundamentos de Enfermería', 'Enfermería Materno Infantil', 'Farmacología', 'Salud Pública'],
        'BQF' => ['Química Analítica', 'Farmacología General', 'Microbiología', 'Toxicología'],
        'DER' => ['Derecho Civil I', 'Derecho Penal I', 'Derecho Constitucional', 'Derecho Romano'],
        'EDU' => ['Pedagogía General', 'Didáctica General', 'Psicología Educativa', 'Curriculum Educativo'],
        'CPU' => ['Contabilidad General', 'Contabilidad de Costos', 'Auditoría I', 'Tributación'],
        'ADM' => ['Administración General', 'Gestión de Recursos Humanos', 'Marketing I', 'Planificación Estratégica'],
        'ECO' => ['Microeconomía I', 'Macroeconomía I', 'Econometría I', 'Economía Boliviana'],
        'COM' => ['Teoría de la Comunicación', 'Redacción Periodística', 'Comunicación Audiovisual', 'Opinión Pública'],
        'TSO' => ['Fundamentos de Trabajo Social', 'Trabajo Social Familiar', 'Política Social', 'Investigación Social'],
        'PSI' => ['Psicología General', 'Psicología del Desarrollo', 'Psicopatología', 'Psicología Social'],
        'AGR' => ['Edafología', 'Fitotecnia General', 'Fitopatología', 'Riego y Drenaje'],
        'MVZ' => ['Anatomía Veterinaria', 'Fisiología Animal', 'Nutrición Animal', 'Parasitología Veterinaria'],
        'MAT' => ['Álgebra Lineal', 'Cálculo Avanzado', 'Análisis Matemático I', 'Ecuaciones Diferenciales'],
        'TUR' => ['Patrimonio Cultural', 'Gestión Hotelera', 'Planificación Turística', 'Guía de Turismo'],
    ];

    /**
     * Materias generales que varias carreras dictan por su cuenta (cada carrera tiene su
     * propia sección, no son la misma materia repetida).
     */
    private const GENERALES = [
        'Metodología de la Investigación',
        'Comunicación y Redacción',
        'Realidad Nacional',
        'Inglés Técnico I',
        'Estadística General',
        'Informática Básica',
    ];

    public function run(): void
    {
        $carreras = Carrera::all();

        foreach ($carreras as $carrera) {
            $nombres = [
                ...self::ESPECIALIDADES[$carrera->sigla] ?? [],
                ...fake()->randomElements(self::GENERALES, fake()->numberBetween(1, 3)),
            ];

            $numero = 100;
            foreach ($nombres as $nombre) {
                Materia::create([
                    'sigla' => sprintf('%s-%03d', $carrera->sigla, $numero),
                    'nombre' => $nombre,
                    'carrera_id' => $carrera->id,
                    'horas' => fake()->randomElement([2, 4, 4, 6, 6, 6, 8]),
                ]);
                $numero += 10;
            }
        }

        // Materias de servicio: las dicta Matemáticas pero las cursan varias ingenierías
        // (ver MallaCurricularSeeder, que las agrega también a esas mallas).
        $matematicas = $carreras->firstWhere('sigla', 'MAT');
        Materia::create(['sigla' => 'MAT-050', 'nombre' => 'Cálculo I', 'carrera_id' => $matematicas->id, 'horas' => 6]);
        Materia::create(['sigla' => 'MAT-060', 'nombre' => 'Cálculo II', 'carrera_id' => $matematicas->id, 'horas' => 6]);
    }
}

<?php

use App\Models\User;

return [
    'password' => env('DEMO_AUTH_PASSWORD', ''),

    'accounts' => [
        [
            'id' => 'demo-vicerrectorado',
            'name' => 'Vicerrectorado',
            'email' => 'admin@uatf.edu.bo',
            'rol' => User::ROL_VICERRECTORADO,
        ],
        [
            'id' => 'demo-inf',
            'name' => 'Director Ingeniería Informática',
            'email' => 'director.inf@uatf.edu.bo',
            'rol' => User::ROL_DIRECTOR_CARRERA,
            'carrera_id' => 1,
            'carrera' => [
                'sigla' => 'INF',
                'nombre' => 'Ingeniería Informática',
            ],
        ],
        [
            'id' => 'demo-med',
            'name' => 'Director Medicina',
            'email' => 'director.med@uatf.edu.bo',
            'rol' => User::ROL_DIRECTOR_CARRERA,
            'carrera_id' => 2,
            'carrera' => [
                'sigla' => 'MED',
                'nombre' => 'Medicina',
            ],
        ],
        [
            'id' => 'demo-mec',
            'name' => 'Director Ingeniería Mecánica',
            'email' => 'director.mec@uatf.edu.bo',
            'rol' => User::ROL_DIRECTOR_CARRERA,
            'carrera_id' => 3,
            'carrera' => [
                'sigla' => 'MEC',
                'nombre' => 'Ingeniería Mecánica',
            ],
        ],
        [
            'id' => 'demo-decanatura-cp',
            'name' => 'Decanatura de Ciencias Puras',
            'email' => 'decanatura.cp@uatf.edu.bo',
            'rol' => User::ROL_DECANATURA,
            'facultad_id' => 3,
        ],
    ],
];

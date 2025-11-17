<?php
function getRecommendationText(string $area, string $estado): string {
    $area = strtolower($area);
    $estado = strtoupper($estado);

    // Generic recommendations by estado
    $generic = [
        'APTO' => 'Excelente desempeño. Considera explorar carreras y actividades relacionadas para profundizar tus intereses.',
        'POTENCIAL' => 'Buen potencial. Practica y busca experiencias prácticas para evaluar si te gusta profundizar en esta área.',
        'POR REFORZAR' => 'Área a reforzar. Puedes tomar cursos introductorios o actividades extracurriculares para fortalecer habilidades.'
    ];

    if (!isset($generic[$estado])) {
        return 'Explora actividades y recursos para conocer mejor esta área.';
    }

    // Area-specific advice
    $byArea = [
        'ciencias' => [
            'APTO' => 'Te destacas en ciencias: considera materias y club de ciencias, ferias de investigación o cursos de laboratorio.',
            'POTENCIAL' => 'Interés científico presente: prueba proyectos pequeños de investigación o talleres de ciencia.',
            'POR REFORZAR' => 'Si te interesa, empieza con talleres básicos de ciencia y experimentos guiados para ganar confianza.'
        ],
        'tecnologia' => [
            'APTO' => 'Tienes aptitudes en tecnología: explora programación, robótica y participación en hackathons o cursos online.',
            'POTENCIAL' => 'Buen potencial tecnológico: intenta retos de programación básicos o aprende a usar herramientas digitales.',
            'POR REFORZAR' => 'Comienza con cursos introductorios en informática y práctica con proyectos pequeños para mejorar.'
        ],
        'humanidades' => [
            'APTO' => 'Fuerte en humanidades: considera debates, escribir para la revista escolar o estudiar ciencias sociales.',
            'POTENCIAL' => 'Interés en humanidades: participa en clubes de lectura o talleres de comunicación.',
            'POR REFORZAR' => 'Lee más sobre historia y participa en actividades culturales para desarrollar interés.'
        ],
        'artes' => [
            'APTO' => 'Talento artístico: inscríbete en talleres de arte, música o diseño y arma un portafolio de trabajos.',
            'POTENCIAL' => 'Potencial creativo: prueba diferentes disciplinas artísticas para descubrir preferencias.',
            'POR REFORZAR' => 'Toma clases básicas de dibujo, música o teatro para explorar tu interés.'
        ],
        'salud' => [
            'APTO' => 'Interés en salud: considera voluntariado en salud comunitaria o cursos introductorios en ciencias de la salud.',
            'POTENCIAL' => 'Potencial en salud: participa en charlas sobre cuidado y primeros auxilios o talleres básicos.',
            'POR REFORZAR' => 'Comienza con cursos de ciudadanía y salud para entender el campo y decidir si te interesa.'
        ],
        'negocios' => [
            'APTO' => 'Habilidad para negocios: explora emprendimientos escolares, cursos de economía básica y liderazgo.',
            'POTENCIAL' => 'Interés en negocios: participa en proyectos de emprendimiento o simulaciones de empresa.',
            'POR REFORZAR' => 'Amplía tu comprensión con actividades de administración básica y trabajo en equipo.'
        ]
    ];

    if (isset($byArea[$area]) && isset($byArea[$area][$estado])) {
        return $byArea[$area][$estado];
    }

    return $generic[$estado];
}

?>

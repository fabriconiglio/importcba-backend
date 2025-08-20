<?php

return [

    'label' => 'Navegación de paginación',

    'overview' => '{1} Mostrando 1 resultado|[2,*] Mostrando :first a :last de :total resultados',

    'fields' => [

        'records_per_page' => [

            'label' => 'Por página',

            'options' => [
                'all' => 'Todos',
            ],

        ],

    ],

    'actions' => [

        'first' => 'Primera',

        'go_to_page' => 'Ir a la página :page',

        'last' => 'Última',

        'next' => 'Siguiente',

        'previous' => 'Anterior',

    ],

];
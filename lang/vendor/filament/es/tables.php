<?php

return [

    'actions' => [

        'label' => 'Acciones',

        'modal' => [

            'heading' => 'Acciones',

            'actions' => [

                'label' => 'Ejecutar',

            ],

        ],

        'group' => [

            'label' => 'Acciones en grupo',

        ],

        'single' => [

            'label' => 'Acción',

        ],

    ],

    'bulk_actions' => [

        'label' => 'Acciones en grupo',

        'modal' => [

            'heading' => 'Acciones en grupo para :count registro(s)',

            'actions' => [

                'label' => 'Ejecutar',

            ],

        ],

    ],

    'columns' => [

        'text' => [

            'actions_list' => [
                'label' => 'Acciones',
            ],

            'copied' => 'Copiado',

            'copy_to_clipboard' => 'Copiar al portapapeles',

            'search' => [
                'placeholder' => 'Buscar...',
            ],

        ],

        'toggle_columns' => 'Alternar columnas',

    ],

    'empty' => [

        'heading' => 'No se encontraron registros',

        'description' => 'Crea un nuevo registro para comenzar.',

    ],

    'filters' => [

        'actions' => [

            'remove' => 'Eliminar filtro',

            'remove_all' => 'Eliminar todos los filtros',

        ],

        'indicator' => 'Filtros activos',

        'modal' => [

            'actions' => [

                'apply' => 'Aplicar',

            ],

        ],

    ],

    'grouping' => [

        'fields' => [

            'label' => 'Agrupar por',

            'placeholder' => 'Seleccionar campo',

        ],

    ],

    'pagination' => [

        'label' => 'Navegación de paginación',

        'overview' => '{1} Se muestra un resultado|[2,*] Se muestran de :first a :last de :total resultados',

        'fields' => [

            'records_per_page' => [

                'label' => 'por página',

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

    ],

    'reorder' => [

        'label' => 'Reordenar registros',

        'modal' => [

            'heading' => 'Reordenar registros',

            'description' => 'Arrastra y suelta los registros para reordenarlos.',

        ],

    ],

    'selection_indicator' => [

        'selected_count' => '{1} registro seleccionado|[2,*] :count registros seleccionados',

        'actions' => [

            'select_all' => 'Seleccionar todos los :count registros',

            'deselect_all' => 'Deseleccionar todos',

        ],

    ],

]; 
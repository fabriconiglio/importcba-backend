<?php

return [

    'columns' => [

        'text' => [
            'more_list_items' => ':count más',
        ],

    ],

    'fields' => [

        'bulk_select_page' => [
            'label' => 'Seleccionar/deseleccionar todos los elementos para acciones masivas.',
        ],

        'bulk_select_record' => [
            'label' => 'Seleccionar/deseleccionar elemento :key para acciones masivas.',
        ],

        'search' => [
            'label' => 'Buscar',
            'placeholder' => 'Buscar',
            'indicator' => 'Buscar',
        ],

    ],

    'summary' => [

        'heading' => 'Resumen',

        'subheadings' => [
            'all' => 'Todos los :label',
            'group' => 'Grupo :group',
            'page' => 'Esta página',
        ],

        'summarizers' => [

            'average' => [
                'label' => 'Promedio',
            ],

            'count' => [
                'label' => 'Contar',
            ],

            'sum' => [
                'label' => 'Suma',
            ],

        ],

    ],

    'actions' => [

        'disable_reordering' => [
            'label' => 'Finalizar reordenamiento de registros',
        ],

        'enable_reordering' => [
            'label' => 'Reordenar registros',
        ],

        'filter' => [
            'label' => 'Filtrar',
        ],

        'group' => [
            'label' => 'Agrupar',
        ],

        'open_bulk_actions' => [
            'label' => 'Acciones en grupo',
        ],

        'toggle_columns' => [
            'label' => 'Alternar columnas',
        ],

    ],

    'empty' => [

        'heading' => 'Sin :model encontrados',

        'description' => 'Crea un :model para empezar.',

    ],

    'filters' => [

        'actions' => [

            'remove' => [
                'label' => 'Quitar filtro',
            ],

            'remove_all' => [
                'label' => 'Quitar todos los filtros',
            ],

            'reset' => [
                'label' => 'Restablecer',
            ],

        ],

        'heading' => 'Filtros',

        'indicator' => 'Filtros activos',

        'multi_select' => [
            'placeholder' => 'Todo',
        ],

        'select' => [
            'placeholder' => 'Todo',
        ],

        'trinary' => [

            'placeholder' => 'Todo',

            'true' => 'Sí',

            'false' => 'No',

        ],

    ],

    'grouping' => [

        'fields' => [

            'group' => [
                'label' => 'Agrupar por',
                'placeholder' => 'Agrupar por',
            ],

            'direction' => [

                'label' => 'Dirección del grupo',

                'options' => [
                    'asc' => 'Ascendente',
                    'desc' => 'Descendente',
                ],

            ],

        ],

    ],

    'reorder_indicator' => 'Arrastra y suelta los registros en orden.',

    'selection_indicator' => [

        'selected_count' => '1 registro seleccionado|:count registros seleccionados',

        'actions' => [

            'select_all' => [
                'label' => 'Seleccionar los :count',
            ],

            'deselect_all' => [
                'label' => 'Deseleccionar todo',
            ],

        ],

    ],

    'sorting' => [

        'fields' => [

            'column' => [
                'label' => 'Ordenar por',
            ],

            'direction' => [

                'label' => 'Dirección de ordenamiento',

                'options' => [
                    'asc' => 'Ascendente',
                    'desc' => 'Descendente',
                ],

            ],

        ],

    ],

];
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Performance Optimization Settings
    |--------------------------------------------------------------------------
    |
    | This file contains various performance optimization settings for the
    | application, including Filament admin panel optimizations.
    |
    */

    'filament' => [
        'debounce' => [
            'text_input' => env('FILAMENT_DEBOUNCE_TEXT', 300),
            'textarea' => env('FILAMENT_DEBOUNCE_TEXTAREA', 500),
            'toggle' => env('FILAMENT_DEBOUNCE_TOGGLE', 200),
        ],
        
        'live_updates' => [
            'enabled' => env('FILAMENT_LIVE_UPDATES', true),
            'delay' => env('FILAMENT_LIVE_DELAY', 500),
        ],
        
        'form_optimization' => [
            'disable_autocomplete' => env('FILAMENT_DISABLE_AUTOCOMPLETE', true),
            'disable_spellcheck' => env('FILAMENT_DISABLE_SPELLCHECK', true),
            'optimize_validation' => env('FILAMENT_OPTIMIZE_VALIDATION', true),
        ],
    ],

    'sessions' => [
        'cleanup' => [
            'enabled' => env('SESSION_CLEANUP_ENABLED', true),
            'schedule' => env('SESSION_CLEANUP_SCHEDULE', 'daily'),
            'keep_days' => env('SESSION_KEEP_DAYS', 7),
        ],
    ],

    'cache' => [
        'filament_forms' => [
            'enabled' => env('CACHE_FILAMENT_FORMS', true),
            'ttl' => env('CACHE_FILAMENT_FORMS_TTL', 3600), // 1 hora
        ],
    ],
]; 
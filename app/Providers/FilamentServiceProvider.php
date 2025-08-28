<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;

class FilamentServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Configurar componentes de formulario por defecto para mejor rendimiento
        TextInput::configureUsing(function (TextInput $component): void {
            $component
                ->extraInputAttributes([
                    'autocomplete' => 'off',
                    'spellcheck' => 'false',
                ])
                ->debounce(300); // Debounce por defecto para todos los inputs
        });

        Textarea::configureUsing(function (Textarea $component): void {
            $component
                ->extraInputAttributes([
                    'autocomplete' => 'off',
                ])
                ->debounce(500); // Debounce más largo para textareas
        });

        Toggle::configureUsing(function (Toggle $component): void {
            $component
                ->debounce(200); // Debounce corto para toggles
        });
    }
} 
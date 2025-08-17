<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->colors([
                'primary' => '#2392D2',   // Celeste
                'danger'  => '#EB4B2C',   // Rojo
                'warning' => '#FEE050',   // Amarillo
                'info'    => '#2C4B8E',   // Azul
                'success' => '#02955F',   // Verde
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\SalesKPIs::class,
                \App\Filament\Widgets\LowStockAlert::class,
                \App\Filament\Widgets\ProductAnalytics::class,
                \App\Filament\Widgets\SalesByCategoryChart::class,
                \App\Filament\Widgets\StoreManagementOverview::class,
                \App\Filament\Widgets\CouponUsageChart::class,
                \App\Filament\Widgets\Ingresos::class,
                \App\Filament\Widgets\PedidosPorMes::class,
                \App\Filament\Widgets\TotalClientes::class,
                \App\Filament\Widgets\OrdersOverview::class,
                \App\Filament\Widgets\OrdersChart::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->brandLogo(asset('images/logo.png'))
            ->brandLogoHeight('5rem');
    }
}

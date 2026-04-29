<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Auth\Login;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
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
            ->login(Login::class) // Custom login split-screen

            ->brandName('Auliachem CRM')
            ->viteTheme('resources/css/filament/admin/theme.css')

            ->colors([
                'primary' => Color::Indigo,
                'gray'    => Color::Slate,
                'info'    => Color::Sky,
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger'  => Color::Rose,
            ])

            ->darkMode(true)
            ->maxContentWidth(MaxWidth::Full)
            ->sidebarCollapsibleOnDesktop()
            ->sidebarWidth('16rem')

            ->navigationGroups([
                NavigationGroup::make('CRM')->icon('heroicon-o-briefcase'),
                NavigationGroup::make('Transaksi')->icon('heroicon-o-document-text'),
                NavigationGroup::make('Master Data')->icon('heroicon-o-circle-stack'),
                NavigationGroup::make('Manajemen')->icon('heroicon-o-cog-6-tooth'),
            ])

            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([\App\Filament\Pages\Dashboard::class])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                \App\Filament\Widgets\StatsOverviewWidget::class,
                \App\Filament\Widgets\RevenueChartWidget::class,
                \App\Filament\Widgets\PoVolumeChartWidget::class,
                \App\Filament\Widgets\PoByCategoryWidget::class,
                \App\Filament\Widgets\TopCustomersWidget::class,
                \App\Filament\Widgets\RecentActivitiesWidget::class,
                Widgets\AccountWidget::class,
            ])

            ->plugins([
                FilamentShieldPlugin::make()
                    ->gridColumns(['default' => 2, 'sm' => 3, 'lg' => 4])
                    ->sectionColumnSpan(1)
                    ->checkboxListColumns(['default' => 1, 'sm' => 2])
                    ->resourceCheckboxListColumns(['default' => 1, 'sm' => 2]),
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
            ->authMiddleware([Authenticate::class]);
    }
}

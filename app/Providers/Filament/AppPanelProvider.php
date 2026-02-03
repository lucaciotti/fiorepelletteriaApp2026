<?php

namespace App\Providers\Filament;

use Filament\Panel;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Filament\Pages;
use Filament\PanelProvider;
use App\Providers\Filament\Traits\HasCorePanel;
use Filament\Navigation\NavigationGroup;
use TomatoPHP\FilamentPWA\FilamentPWAPlugin;

class AppPanelProvider extends PanelProvider
{
    use HasCorePanel;
    
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('app')
            ->path('')
            ->pages([
                // Pages\Dashboard::class,
            ])
            ->plugins([
                FilamentPWAPlugin::make()->allowPWASettings(false),
            ])
            // ->navigationGroups([
            //     NavigationGroup::make()
            //         ->label('Ordini')
            //         ->icon('heroicon-o-clipboard-document-list'),
            //     NavigationGroup::make()
            //         ->label('Blog')
            //         ->icon('heroicon-o-pencil'),
            //     NavigationGroup::make()
            //         ->label(fn(): string => __('navigation.settings'))
            //         ->icon('heroicon-o-cog-6-tooth')
            //         ->collapsed(),
            // ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets');
    }
}

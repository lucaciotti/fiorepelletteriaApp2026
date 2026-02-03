<?php

namespace App\Providers\Filament;

use Filament\Panel;
use Filament\PanelProvider;
use App\Providers\Filament\Traits\HasCorePanel;
use TomatoPHP\FilamentPWA\FilamentPWAPlugin;

class ConfigPanelProvider extends PanelProvider
{
    use HasCorePanel;

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('config')
            ->path('config')
            ->plugins([
                \TomatoPHP\FilamentUsers\FilamentUsersPlugin::make(),
                FilamentPWAPlugin::make()->allowPWASettings(true),
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
                \Boquizo\FilamentLogViewer\FilamentLogViewerPlugin::make()
                    ->navigationGroup('System')
                    ->navigationSort(2)
                    // ->navigationIcon(Heroicon::OutlinedDocumentText)
                    ->navigationLabel('Log Viewer')
                // ->authorize(fn(): bool => auth()->user()->can('view-logs')),
                // Other plugins
            ])
            ->pages([
                // Pages\Dashboard::class,
            ])
            ->discoverResources(in: app_path('Filament/Config/Resources'), for: 'App\Filament\Config\Resources')
            ->discoverPages(in: app_path('Filament/Config/Pages'), for: 'App\Filament\Config\Pages');
    }
}

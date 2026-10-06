<?php

namespace App\Providers\Filament;

use App\Providers\Filament\Traits\HasCorePanel;
use Filament\Panel;
use Filament\PanelProvider;

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
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
                \Boquizo\FilamentLogViewer\FilamentLogViewerPlugin::make()
                    ->navigationGroup('System')
                    ->navigationSort(2)
                    // ->navigationIcon(Heroicon::OutlinedDocumentText)
                    ->navigationLabel('Log Viewer'),
                // ->authorize(fn(): bool => auth()->user()->can('view-logs')),
                // Other plugins
            ])
            ->pages([
                \App\Filament\Config\Pages\ManageWorkOrderSettings::class,
            ])
            ->discoverResources(in: app_path('Filament/Config/Resources'), for: 'App\Filament\Config\Resources');
    }
}

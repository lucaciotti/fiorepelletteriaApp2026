<?php

namespace App\Providers\Filament;

use App\Providers\Filament\Traits\HasCorePanel;
use Filament\Panel;
use Filament\PanelProvider;
use TomatoPHP\FilamentPWA\FilamentPWAPlugin;

class AdminPanelProvider extends PanelProvider
{
    use HasCorePanel;

    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('admin')
            ->path('admin')
            ->plugins([
                FilamentPWAPlugin::make()->allowPWASettings(false),
            ])
            ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\Filament\Admin\Resources')
            ->discoverPages(in: app_path('Filament/Admin/Pages'), for: 'App\Filament\Admin\Pages');
    }
}

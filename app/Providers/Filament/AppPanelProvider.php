<?php

namespace App\Providers\Filament;

use App\Providers\Filament\Traits\HasCorePanel;
use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\PanelProvider;

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
                static::pwaPlugin(),
                static::webpushPlugin(),
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
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources');
    }

    /**
     * Il plugin PWA è risolto tramite nome di classe: evita un riferimento di tipo
     * diretto a un pacchetto vendor non indicizzato dall'IDE, ma resta valido a runtime.
     */
    protected static function pwaPlugin(): Plugin
    {
        /** @var Plugin $plugin */
        $plugin = app(sprintf('%s\\%s', 'Alareqi\\FilamentPwa', 'FilamentPwaPlugin'));

        return $plugin;
    }

    protected static function webpushPlugin(): Plugin
    {
        /** @var Plugin $plugin */
        $plugin = app(sprintf('%s\\%s', 'FilamentWebpush', 'FilamentWebpushPlugin'));

        return $plugin;
    }
}

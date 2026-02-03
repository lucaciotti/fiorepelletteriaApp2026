<?php

namespace App\Providers;

use BezhanSalleh\PanelSwitch\PanelSwitch;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Table;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\ServiceProvider;
use TomatoPHP\FilamentUsers\Filament\Resources\Users\Schemas\UserForm;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->environment('production')) {
            \URL::forceScheme('https');
        }
        
        PanelSwitch::configureUsing(function (PanelSwitch $panelSwitch) {
            // $panelSwitch->modalHeading('Available Panels');
            $panelSwitch->simple();
            $panelSwitch->renderHook(PanelsRenderHook::USER_MENU_BEFORE);
            $panelSwitch->labels([
                'app' => "Home",
                'admin' => 'Main',
                'config' => "Configurazioni",
            ]);
            if(auth()->user() && !auth()->user()->hasRole('admin') && !auth()->user()->hasRole('super_admin')){
                $panelSwitch->panels(['app']);
            } else {
                $panelSwitch->panels(['app', 'admin', 'config']);
            }
            // $panelSwitch->renderHook(PanelsRenderHook::USER_MENU_AFTER);
            // $panelSwitch->modalWidth('sm');
            // $panelSwitch->icons([
            //     'app' => 'heroicon-o-square-2-stack',
            //     'config' => 'heroicon-o-star',
            // ], $asImage = false);
        });
        UserForm::register([
            Select::make('operator_id')
                ->label('Operatore di Riferimento')
                ->relationship('operator', 'name')
                ->searchable()
                ->preload()
                ->createOptionForm([
                    TextInput::make('name')
                        ->label('Nome')
                        ->required()
                        ->maxLength(255),
                    TextInput::make('description')
                        ->label('Descrizione')
                        ->required()
                        ->maxLength(255),
                ]),
        ]);
        Table::configureUsing(
            fn(Table $table) => $table
                ->defaultPaginationPageOption(25)
        );
    }
}

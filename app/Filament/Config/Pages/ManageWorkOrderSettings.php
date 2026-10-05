<?php

namespace App\Filament\Config\Pages;

use App\Settings\WorkOrderSettings;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Toggle;
use Filament\Pages\SettingsPage;
use Filament\Schemas\Schema;
use UnitEnum;

class ManageWorkOrderSettings extends SettingsPage
{
    protected static string $settings = WorkOrderSettings::class;

    protected static ?string $title = 'Lavorazioni aperte';

    protected static string|UnitEnum|null $navigationGroup = 'Sistema';

    protected static ?int $navigationSort = 1;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-clock';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Toggle::make('enabled')
                    ->label('Controllo giornaliero attivo')
                    ->helperText('Se attivo, all\'ora indicata il sistema notifica (campanella + push) le lavorazioni non in pausa e non terminate.'),
                TimePicker::make('alert_hour')
                    ->label('Ora del controllo')
                    ->helperText('Ora del giorno in cui eseguire il controllo.')
                    ->seconds(false)
                    ->format('H:i')
                    ->required(),
            ]);
    }
}

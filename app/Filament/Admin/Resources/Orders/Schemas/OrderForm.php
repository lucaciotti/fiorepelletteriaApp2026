<?php

namespace App\Filament\Admin\Resources\Orders\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class OrderForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Fieldset::make('Anagrafiche')->columns(3)->schema([
                    TextInput::make('number')->label('Numero Ordine')
                        ->required(),
                    Select::make('customer_id')
                        ->label('Cliente')
                        ->relationship('customer', 'name')
                        ->searchable()
                        ->preload()
                        ->createOptionForm([
                            TextInput::make('name')
                                ->label('Ragione Sociale')
                                ->required()
                                ->maxLength(255),
                            TextInput::make('subname')
                                ->label('Descrizione')
                                ->maxLength(255),
                            TextInput::make('tva')
                                ->label('P.Iva')
                                ->maxLength(255),
                            TextInput::make('localita')
                                ->label('Località')
                                ->maxLength(255),
                            TextInput::make('indirizzo')
                                ->label('Indirizzo')
                                ->maxLength(255),
                        ]),
                    DateTimePicker::make('date')->label('Data Ordine')
                        ->required(),
                    Toggle::make('closed')->label('Ordine Chiuso')
                ]),
                Section::make('Righe')->collapsible()
                    ->schema([
                        Repeater::make('rows')->label('Prodotti')->columns(2)->relationship()
                            ->schema([
                                Select::make('product_id')
                                    ->label('Prodotto')
                                    ->relationship('product', 'code')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->createOptionForm([
                                        TextInput::make('code')
                                            ->label('Codice Prodotto')
                                            ->required()
                                            ->maxLength(255),
                                        TextInput::make('description')
                                            ->label('Descrizione')
                                            ->maxLength(255),
                                    ]),
                                TextInput::make('quantity')->label('Quantità')
                                    ->visible()
                                    ->required()
                                    ->numeric(),
                                Toggle::make('closed')->label('Riga Evasa')
                            ]),
                    ]),
            ]);
    }
}

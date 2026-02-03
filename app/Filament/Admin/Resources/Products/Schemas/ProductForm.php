<?php

namespace App\Filament\Admin\Resources\Products\Schemas;

use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Repeater\TableColumn;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ProductForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Fieldset::make('')
                ->schema([
                    TextInput::make('code')->label('Codice')
                        ->required(),
                    TextInput::make('description')->label('Descrizione')
                        ->required(),
                ]),
                Section::make('Lavorazioni Collegate')->collapsible()->columns(1)
                    ->schema([
                        Repeater::make('productProcessTypes')->label('Lavorazioni')->hiddenLabel(true)->relationship()
                            ->table([
                                TableColumn::make('#')->width('30px'),
                                TableColumn::make('Lavorazione'),
                            ])
                            ->compact()
                            ->schema([
                                TextInput::make('position')->readOnly(true)->live(),
                                Select::make('process_type_id')
                                    ->label('Lavorazione')
                                    ->relationship('processType', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                    // ->createOptionForm([
                                    //     TextInput::make('code')
                                    //         ->label('Codice Prodotto')
                                    //         ->required()
                                    //         ->maxLength(255),
                                    //     TextInput::make('description')
                                    //         ->label('Descrizione')
                                    //         ->maxLength(255),
                                    // ]),
                            ])->itemNumbers()->orderColumn('position'),
                    ]),
            ]);
    }
}

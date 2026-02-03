<?php

namespace App\Filament\Admin\Resources\ProcessTypes\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ProcessTypeForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')->label('Codice')
                    ->required(),
                TextInput::make('name')->label('Nome')
                    ->required(),
                Select::make('process_type_category_id')
                    ->label('Categoria di Lavorazione')
                    ->relationship('category', 'name')
                    ->searchable()
                    ->preload()
                    ->createOptionForm([
                        TextInput::make('name')
                            ->label('Nome Categoria')
                            ->required()
                            ->maxLength(255),
                    ]),
                TextInput::make('description')->label('Descrizione')
                    ->required(),
            ]);
    }
}

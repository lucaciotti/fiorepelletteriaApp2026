<?php

namespace App\Filament\Admin\Resources\Customers\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->label('Nome Cliente')
                    ->required(),
                TextInput::make('subname')->label('Descrizione'),
                TextInput::make('tva')->label('P.Iva'),
                TextInput::make('localita')->label('Località'),
                TextInput::make('indirizzo')->label('Indirizzo'),
            ]);
    }
}

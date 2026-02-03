<?php

namespace App\Filament\Config\Resources\Operators\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class OperatorForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('description')
                    ->required(),
            ]);
    }
}

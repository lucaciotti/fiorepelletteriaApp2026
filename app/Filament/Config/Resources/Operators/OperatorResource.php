<?php

namespace App\Filament\Config\Resources\Operators;

use App\Filament\Config\Resources\Operators\Pages\CreateOperator;
use App\Filament\Config\Resources\Operators\Pages\EditOperator;
use App\Filament\Config\Resources\Operators\Pages\ListOperators;
use App\Filament\Config\Resources\Operators\Pages\ViewOperator;
use App\Filament\Config\Resources\Operators\Schemas\OperatorForm;
use App\Filament\Config\Resources\Operators\Schemas\OperatorInfolist;
use App\Filament\Config\Resources\Operators\Tables\OperatorsTable;
use App\Models\Operator;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class OperatorResource extends Resource
{
    protected static ?string $model = Operator::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'name';
    protected static ?string $modelLabel = 'operatore';
    protected static ?string $pluralModelLabel = 'operatori';

    public static function form(Schema $schema): Schema
    {
        return OperatorForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return OperatorInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return OperatorsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOperators::route('/'),
            // 'create' => CreateOperator::route('/create'),
            'view' => ViewOperator::route('/{record}'),
            // 'edit' => EditOperator::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Admin\Resources\ProcessTypes;

use App\Filament\Admin\Resources\ProcessTypes\Pages\CreateProcessType;
use App\Filament\Admin\Resources\ProcessTypes\Pages\EditProcessType;
use App\Filament\Admin\Resources\ProcessTypes\Pages\ListProcessTypes;
use App\Filament\Admin\Resources\ProcessTypes\Pages\ViewProcessType;
use App\Filament\Admin\Resources\ProcessTypes\Schemas\ProcessTypeForm;
use App\Filament\Admin\Resources\ProcessTypes\Schemas\ProcessTypeInfolist;
use App\Filament\Admin\Resources\ProcessTypes\Tables\ProcessTypesTable;
use App\Models\ProcessType;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class ProcessTypeResource extends Resource
{
    protected static ?string $model = ProcessType::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBars2;

    protected static string | UnitEnum | null $navigationGroup = 'Anagrafiche';
    protected static ?int $navigationSort = 4;
    
    protected static ?string $recordTitleAttribute = 'description';
    protected static ?string $modelLabel = 'tipo lavorazione';
    protected static ?string $pluralModelLabel = 'tipi lavorazione';

    public static function form(Schema $schema): Schema
    {
        return ProcessTypeForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ProcessTypeInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProcessTypesTable::configure($table);
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
            'index' => ListProcessTypes::route('/'),
            // 'create' => CreateProcessType::route('/create'),
            // 'view' => ViewProcessType::route('/{record}'),
            // 'edit' => EditProcessType::route('/{record}/edit'),
        ];
    }
}

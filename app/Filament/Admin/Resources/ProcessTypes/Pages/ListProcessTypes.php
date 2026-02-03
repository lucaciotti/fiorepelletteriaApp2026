<?php

namespace App\Filament\Admin\Resources\ProcessTypes\Pages;

use App\Filament\Admin\Resources\ProcessTypes\ProcessTypeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListProcessTypes extends ListRecords
{
    protected static string $resource = ProcessTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}

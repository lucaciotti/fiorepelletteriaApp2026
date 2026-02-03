<?php

namespace App\Filament\Admin\Resources\ProcessTypes\Pages;

use App\Filament\Admin\Resources\ProcessTypes\ProcessTypeResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewProcessType extends ViewRecord
{
    protected static string $resource = ProcessTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}

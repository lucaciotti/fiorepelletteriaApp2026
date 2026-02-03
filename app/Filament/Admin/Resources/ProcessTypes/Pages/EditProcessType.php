<?php

namespace App\Filament\Admin\Resources\ProcessTypes\Pages;

use App\Filament\Admin\Resources\ProcessTypes\ProcessTypeResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditProcessType extends EditRecord
{
    protected static string $resource = ProcessTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

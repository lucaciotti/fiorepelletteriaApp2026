<?php

namespace App\Filament\Resources\WorkOrders\Pages;

use App\Filament\Resources\WorkOrders\WorkOrderResource;
use App\Services\WorkOrderTimerService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditWorkOrder extends EditRecord
{
    protected static string $resource = WorkOrderResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function getSaveFormAction(): Action
    {
        return Action::make('save')
            ->label('Salva Modifiche')
            ->color('warning')
            ->action(fn () => $this->save())
            ->keyBindings(['mod+s']);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Lo stato di pausa è governato da WorkOrderTimerService: il valore presente
        // nel form può essere stale e non deve sovrascrivere quello persistito.
        unset($data['paused']);

        return $data;
    }

    protected function afterSave(): void
    {
        // Riporta in coerenza i segmenti di tempo e il totale dopo un salvataggio manuale.
        app(WorkOrderTimerService::class)->reconcile($this->record);
    }

    protected function getHeaderActions(): array
    {
        return [
            // ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

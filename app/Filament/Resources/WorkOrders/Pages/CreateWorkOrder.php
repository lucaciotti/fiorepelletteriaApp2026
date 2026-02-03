<?php

namespace App\Filament\Resources\WorkOrders\Pages;

use App\Filament\Resources\WorkOrders\WorkOrderResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

class CreateWorkOrder extends CreateRecord
{
    protected static string $resource = WorkOrderResource::class;
    protected static bool $canCreateAnother = false;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }


    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['start_at'] = now();
        return $data;
    }

    protected function getCreateFormAction(): Action
    {
        // if ($this->data['start_at'] == null) {
        //     return Action::make('create')
        //         ->label(__('filament-panels::resources/pages/create-record.form.actions.create.label'))
        //         ->modalDescription('Non è stata configurato "Inizio Lavorazione"! Proseguire?')
        //         ->requiresConfirmation()
        //         ->action(fn() => $this->create())
        //         ->keyBindings(['mod+s']);
        // } else {
        //     return Action::make('create')
        //         ->label(__('filament-panels::resources/pages/create-record.form.actions.create.label'))
        //         // ->requiresConfirmation()
        //         ->action(fn() => $this->create())
        //         ->keyBindings(['mod+s']);
        // }
        return Action::make('create')
            ->label('Avvia Lavorazione')
            ->icon('heroicon-m-play-circle')
            ->color('success')
            ->modalDescription('Al salvataggio verrà impostato l\'"Inizio Lavorazione". Proseguire?')
            ->requiresConfirmation()
            ->action(fn() => $this->create())
            ->keyBindings(['mod+s']);
    }
}

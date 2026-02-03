<?php

namespace App\Filament\Resources\WorkOrders\Pages;

use App\Filament\Resources\WorkOrders\WorkOrderResource;
use App\Models\WorkOrdersRecordTime;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Utilities\Get;

class EditWorkOrder extends EditRecord
{
    protected static string $resource = WorkOrderResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function getSaveFormAction(): Action
    {
        if ($this->data['start_at'] == null) {
            return Action::make('create')
                ->label(__('filament-panels::resources/pages/create-record.form.actions.create.label'))
                ->modalDescription('Non è stata configurato "Inizio Lavorazione"! Proseguire?')
                ->requiresConfirmation()
                ->action(fn() => $this->save())
                ->keyBindings(['mod+s']);
        }
        if ($this->data['end_at'] == null) {
            return Action::make('create')
                ->label(__('filament-panels::resources/pages/create-record.form.actions.create.label'))
                ->modalDescription('Non è stata configurato "Fine Lavorazione"! Proseguire?')
                ->requiresConfirmation()
                ->action(fn() => $this->save())
                ->keyBindings(['mod+s']);
        }

        return Action::make('create')
            ->label('Salva Modifiche')
            ->color('warning')
            ->hidden(fn(Get $get) => $get('end_at') != null)
            // ->requiresConfirmation()
            ->action(fn() => $this->save())
            ->keyBindings(['mod+s']);
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // dd($this);  
        if ($data['end_at'] != null) {
            $total_minutes = 0;
            $first_start_at = $data['start_at'];
            $latest_end_at = $data['end_at'];
            $delta_rows = 0;
            $delta_tot_rows = count($this->data['recordsTime']);
            $last_end_at = null;
            foreach ($this->data['recordsTime'] as $deltatime) {
                $delta_rows ++;
                $start_at = !empty($deltatime['start_at']) ? $deltatime['start_at'] : ($delta_rows == 1 ? $first_start_at : $last_end_at);
                $end_at = !empty($deltatime['end_at']) ? $deltatime['end_at'] : ($delta_rows == $delta_tot_rows ? $latest_end_at : $start_at);
                if(!empty($deltatime['total_minutes'])){
                    $total_minutes += $deltatime['total_minutes'];
                } else {
                    $total_minutes += round(Carbon::createFromDate($start_at)->diffInMinutes(Carbon::createFromDate($end_at)), 0);                        
                }
                $last_end_at = $end_at;
            }
            $data['total_minutes'] = $total_minutes;
        }
        return $data;
    }


    protected function getHeaderActions(): array
    {
        return [
            // ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}

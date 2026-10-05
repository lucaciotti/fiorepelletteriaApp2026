<?php

namespace App\Filament\Resources\WorkOrders\Pages;

use App\Filament\Resources\WorkOrders\WorkOrderResource;
use App\Models\User;
use App\Models\WorkOrder;
use App\Models\WorkOrdersRecordTime;
use App\Services\WorkOrderTimerService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditWorkOrder extends EditRecord
{
    protected static string $resource = WorkOrderResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->previousUrl ?? $this->getResource()::getUrl('index');
    }

    protected function getSaveFormAction(): Action
    {
        // Il salvataggio è consentito solo a lavorazione conclusa.
        return Action::make('save')
            ->label('Salva Modifiche')
            ->color('warning')
            ->disabled($this->getWorkOrder()?->end_at === null)
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
            Action::make('registroTempi')
                ->label('Dettaglio registro tempi lavorazione')
                ->icon('heroicon-m-clock')
                ->color('gray')
                ->modalHeading('Registro tempi lavorazione')
                ->modalWidth('4xl')
                ->modalSubmitAction(fn (): bool => static::isAdmin())
                ->modalSubmitActionLabel('Salva registro')
                ->modalCancelActionLabel('Chiudi')
                ->fillForm(fn (): array => ['recordsTime' => $this->getRecordTimeRows()])
                ->schema(fn (): array => [
                    Repeater::make('recordsTime')
                        ->label('Segmenti di tempo')
                        ->addable(static::isAdmin())
                        ->deletable(static::isAdmin())
                        ->reorderable(false)
                        ->columns(2)
                        ->schema([
                            Hidden::make('id'),
                            DateTimePicker::make('start_at')
                                ->label('Inizio')
                                ->seconds(true)
                                ->required()
                                ->readOnly(! static::isAdmin()),
                            DateTimePicker::make('end_at')
                                ->label('Fine')
                                ->seconds(true)
                                ->after('start_at')
                                ->readOnly(! static::isAdmin()),
                        ]),
                ])
                ->action(function (array $data): void {
                    $this->syncRecordTimes($data['recordsTime'] ?? []);
                }),
            DeleteAction::make(),
        ];
    }

    /**
     * Persistenza esplicita dei segmenti modificati nel popup e ricalcolo dei minuti.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    protected function syncRecordTimes(array $rows): void
    {
        $record = $this->getWorkOrder();

        if (! $record) {
            return;
        }

        $keptIds = [];

        foreach ($rows as $row) {
            if (blank($row['start_at'] ?? null)) {
                continue;
            }

            $attributes = [
                'start_at' => $row['start_at'],
                'end_at' => $row['end_at'] ?? null,
            ];

            $segment = filled($row['id'] ?? null)
                ? $record->recordsTime()->whereKey($row['id'])->first()
                : null;

            if ($segment) {
                $segment->update($attributes);
            } else {
                $segment = $record->recordsTime()->create($attributes);
            }

            $keptIds[] = $segment->id;
        }

        $record->recordsTime()->whereNotIn('id', $keptIds)->delete();

        app(WorkOrderTimerService::class)->recomputeTotalMinutes($record);

        $record->refresh();
        $this->fillForm();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getRecordTimeRows(): array
    {
        $record = $this->getWorkOrder();

        if (! $record) {
            return [];
        }

        return $record->recordsTime()
            ->orderBy('start_at')
            ->get()
            ->map(fn (WorkOrdersRecordTime $segment): array => [
                'id' => $segment->id,
                'start_at' => $segment->start_at,
                'end_at' => $segment->end_at,
            ])
            ->all();
    }

    protected function getWorkOrder(): ?WorkOrder
    {
        $record = $this->getRecord();

        return $record instanceof WorkOrder ? $record : null;
    }

    protected static function isAdmin(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasRole(['admin', 'super_admin']);
    }
}

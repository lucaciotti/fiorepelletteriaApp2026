<?php

namespace App\Filament\Resources\WorkOrders\Pages;

use App\Filament\Resources\WorkOrders\WorkOrderResource;
use App\Models\WorkOrder;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListWorkOrders extends ListRecords
{
    protected static string $resource = WorkOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public function getTabs(): array
    {
        return [
            'Tutti' => Tab::make(),
            'In Esecuzione' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('end_at', null)->where('paused', false))
                ->badge(WorkOrder::query()->where('end_at', null)->where('paused', false)->count())
                ->badgeColor('danger'),
            'In Pausa!' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('end_at', null)->where('paused', true))
                ->badge(WorkOrder::query()->where('end_at', null)->where('paused', true)->count())
                ->badgeColor('warning'),
            'Finiti' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('end_at', '!=', null))
                ->badge(WorkOrder::query()->where('end_at', '!=', null)->count())
                ->badgeColor('success'),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'Tutti';
    }
}

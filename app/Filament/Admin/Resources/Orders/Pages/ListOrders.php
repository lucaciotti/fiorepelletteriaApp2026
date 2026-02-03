<?php

namespace App\Filament\Admin\Resources\Orders\Pages;

use App\Filament\Admin\Resources\Orders\OrderResource;
use App\Models\Order;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

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
            'Aperti' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('closed',  false))
                ->badge(Order::query()->where('closed', false)->count())
                ->badgeColor('warning'),
            'Chiusi' => Tab::make()
                ->modifyQueryUsing(fn(Builder $query) => $query->where('closed', true))
                ->badge(Order::query()->where('closed', true)->count())
                ->badgeColor('success'),
        ];
    }

    public function getDefaultActiveTab(): string | int | null
    {
        return 'Tutti';
    }
}

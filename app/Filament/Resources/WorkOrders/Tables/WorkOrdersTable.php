<?php

namespace App\Filament\Resources\WorkOrders\Tables;

use App\Models\Customer;
use App\Models\Operator;
use App\Models\ProcessType;
use App\Models\Product;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Auth;
use Filament\Schemas\Components\Utilities\Get;

class WorkOrdersTable
{
    public static function configure(Table $table): Table
    {
        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin')){
            $table->modifyQueryUsing(fn(Builder $query) => $query->where('operator_id', Auth::user()->operator_id));
        }
        return $table            
            ->defaultSort(function (Builder $query): Builder {
                return $query
                    ->orderBy('start_at', 'desc');
            })
            ->columns([
                IconColumn::make('status')
                    ->label('Stato')
                    ->icon(fn(string $state): Heroicon => match ($state) {
                        'started' => Heroicon::OutlinedPlayCircle,
                        'paused' => Heroicon::OutlinedPauseCircle,
                        'ended' => Heroicon::OutlinedCheckCircle,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'started' => 'danger',
                        'paused' => 'warning',
                        'ended' => 'success',
                        default => 'gray',
                    })
                    ->width('1%'),
                TextColumn::make('ordrif')
                    ->label('Ordine Cliente')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('processType.full_descr')
                    ->label('Tipo Lavorazione')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('operator.name')
                    ->label('Operatore')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('customer.name')
                    ->label('Cliente')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('product.code')
                    ->label('Prodotto')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('quantity')
                    ->label('Qta')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('start_at')
                    ->dateTime()
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('end_at')
                    ->dateTime()
                    ->sortable()->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_minutes')
                    ->label('Tempo Lavorazione (min.)')
                    ->numeric()->hidden(fn(Get $get) => !Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin'))
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                // TextColumn::make('updated_at')
                //     ->dateTime()
                //     ->sortable()
                //     ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->deferColumnManager(false)
            ->filters([
                DateRangeFilter::make('start_at')->label('Data inizio lavorazione'),
                DateRangeFilter::make('end_at')->label('Data inizio lavorazione'),
                SelectFilter::make('customer')->label('Clienti')
                    ->relationship('customer', 'name')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('order')->label('Ordine Cliente')
                    ->relationship('order', 'number')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('operator_id')->label('Operatore')
                    ->searchable()
                    ->options(fn(): array => Operator::query()->pluck('name', 'id')->all()),
                SelectFilter::make('product')->label('Prodotto')
                    ->relationship('product', 'code')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('process_type_id')->label('Lavorazione')
                    ->searchable()
                    ->options(fn(): array => ProcessType::query()->pluck('description', 'id')->all()),
            ], layout: FiltersLayout::Modal)->filtersTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->slideOver()
                    ->label(__('Filter')),
            )->deferFilters(false)
            ->recordActions([
                // ViewAction::make()->slideOver(),
                EditAction::make(),

            ])
            ->toolbarActions([
                ExportAction::make()->exports([
                    ExcelExport::make('table')->fromTable(),
                ]),
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

<?php

namespace App\Filament\Resources\WorkOrders\Tables;

use App\Models\Operator;
use App\Models\ProcessType;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class WorkOrdersTable
{
    public static function configure(Table $table): Table
    {
        if (! static::isAdmin()) {
            $table->modifyQueryUsing(fn (Builder $query) => $query->where('operator_id', static::currentUser()?->operator_id));
        }

        return $table
            ->defaultSort(function (Builder $query): Builder {
                return $query->orderBy('start_at', 'desc');
            })
            ->columns([
                IconColumn::make('status')
                    ->label('Stato')
                    ->icon(fn (string $state): Heroicon => match ($state) {
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
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('end_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('total_minutes')
                    ->label('Tempo Lavorazione (min.)')
                    ->numeric()
                    ->hidden(fn (): bool => ! static::isAdmin())
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->deferColumnManager(false)
            ->filters([
                DateRangeFilter::make('start_at')->label('Data inizio lavorazione'),
                DateRangeFilter::make('end_at')->label('Data fine lavorazione'),
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
                    ->options(fn (): array => Operator::query()->pluck('name', 'id')->all()),
                SelectFilter::make('product')->label('Prodotto')
                    ->relationship('product', 'code')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('process_type_id')->label('Lavorazione')
                    ->searchable()
                    ->options(fn (): array => ProcessType::query()->pluck('description', 'id')->all()),
            ], layout: FiltersLayout::Modal)->filtersTriggerAction(
                fn (Action $action) => $action
                    ->button()
                    ->slideOver()
                    ->label(__('Filter')),
            )->deferFilters(false)
            ->recordActions([
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

    protected static function currentUser(): ?User
    {
        $user = Auth::user();

        return $user instanceof User ? $user : null;
    }

    protected static function isAdmin(): bool
    {
        return static::currentUser()?->hasRole(['admin', 'super_admin']) ?? false;
    }
}

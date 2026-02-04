<?php

namespace App\Filament\Admin\Resources\ProcessTypes\Tables;

use App\Models\ProcessTypeCategory;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProcessTypesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('description')->label('Descrizione')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('category.name')->label('Categoria di Lavorazione')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('process_type_category_id')->label('Categoria')
                    ->searchable()
                    ->options(fn(): array => ProcessTypeCategory::query()->pluck('name', 'id')->all()),
            ], layout: FiltersLayout::Modal)->filtersTriggerAction(
                fn(Action $action) => $action
                    ->button()
                    ->slideOver()
                    ->label(__('Filter')),
            )->deferFilters(false)
            ->recordActions([
                // ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}

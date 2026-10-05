<?php

namespace App\Filament\Resources\WorkOrders\RelationManagers;

use App\Models\User;
use App\Services\WorkOrderTimerService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class RecordTimeRelationManager extends RelationManager
{
    protected static string $relationship = 'recordsTime';

    protected static ?string $title = 'Registro tempi lavorazione';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return true;
    }

    public function isReadOnly(): bool
    {
        // Visibile a tutti; modificabile solo da admin/super_admin.
        return ! static::currentUserIsAdmin();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                DateTimePicker::make('start_at')
                    ->label('Inizio')
                    ->seconds(true)
                    ->required(),
                DateTimePicker::make('end_at')
                    ->label('Fine')
                    ->seconds(true)
                    ->after('start_at'),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('start_at')
            ->defaultSort('start_at')
            ->columns([
                TextColumn::make('start_at')
                    ->label('Inizio')
                    ->dateTime('d/m/Y H:i:s')
                    ->sortable(),
                TextColumn::make('end_at')
                    ->label('Fine')
                    ->dateTime('d/m/Y H:i:s')
                    ->placeholder('— in corso —')
                    ->sortable(),
                TextColumn::make('total_minutes')
                    ->label('Minuti')
                    ->numeric()
                    ->sortable(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->after(fn () => $this->recomputeOwnerTotal()),
            ])
            ->recordActions([
                EditAction::make()
                    ->after(fn () => $this->recomputeOwnerTotal()),
                DeleteAction::make()
                    ->after(fn () => $this->recomputeOwnerTotal()),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->after(fn () => $this->recomputeOwnerTotal()),
                ]),
            ]);
    }

    protected function recomputeOwnerTotal(): void
    {
        app(WorkOrderTimerService::class)->recomputeTotalMinutes($this->getOwnerRecord());
    }

    protected static function currentUserIsAdmin(): bool
    {
        $user = Auth::user();

        return $user instanceof User && $user->hasRole(['admin', 'super_admin']);
    }
}

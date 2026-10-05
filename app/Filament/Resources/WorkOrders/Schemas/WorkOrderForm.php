<?php

namespace App\Filament\Resources\WorkOrders\Schemas;

use App\Models\Order;
use App\Models\OrderRow;
use App\Models\ProcessType;
use App\Models\ProductProcessType;
use App\Models\User;
use App\Models\WorkOrder;
use App\Services\WorkOrderTimerService;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Auth;

class WorkOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        $operator_id = static::currentUser()?->operator_id;

        return $schema
            ->columns(1)
            ->components([
                Fieldset::make('')
                    ->schema([
                        Select::make('order_id')
                            ->label('Ordine Cliente Rif.')
                            // ->relationship('order', 'number')
                            ->options(Order::where('closed', false)->get()->pluck('fulldescr', 'id'))
                            ->live()
                            ->required(),
                        Select::make('order_row_id')
                            ->label('Prodotto di Riferimento')
                            ->options(fn (Get $get) => OrderRow::where('closed', false)->where('order_id', $get('order_id'))->get()->pluck('product.code', 'id'))
                            ->live()
                            ->required(),
                    ]),
                Fieldset::make('Dati produzione')->columns(fn (Get $get) => $get('quantity') > 0 ? 3 : 2)
                    ->schema([
                        Select::make('operator_id')
                            ->default($operator_id)
                            ->label('Operatore Lavorazione')
                            ->disabled(fn () => ! static::isAdmin())
                            ->relationship('operator', 'name')
                            ->required(),
                        Select::make('process_type_id')
                            ->label('Tipo Lavorazione')
                            ->options(function (Get $get) {
                                $orderRow = OrderRow::where('id', $get('order_row_id'))->first();
                                if ($orderRow) {
                                    // $processTypeIds = ProductProcessType::where('product_id', $orderRow->product_id)->orderBy('position')->get()->pluck('process_type_id');
                                    // if (count($processTypeIds)>0){
                                    //     dd(ProcessType::whereIn('id', $processTypeIds)->get()->pluck('full_descr', 'id'));
                                    //     return ProcessType::whereIn('id', $processTypeIds)->get()->pluck('full_descr', 'id');
                                    // }
                                    $processTypes = ProductProcessType::where('product_id', $orderRow->product_id)->orderBy('position')->with('processType')->get();
                                    if (count($processTypes) > 0) {
                                        $aReturn = [];
                                        foreach ($processTypes as $value) {
                                            $aReturn[$value->processType->id] = $value->position.' - '.$value->processType->description;
                                        }

                                        // dd($aReturn);
                                        return $aReturn;
                                    }
                                }

                                return ProcessType::all()->pluck('full_descr', 'id');
                            })
                            // ->options(ProcessType::all()->pluck('full_descr', 'id'))
                            // ->relationship('processType', 'full_descr')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('quantity')->label('Quantità')
                            // ->visible(fn(Get $get) => $get('quantity')>0)
                            ->visible(fn (Get $get) => $get('end_at') != null)
                            ->required()
                            ->numeric(),
                    ]),
                Fieldset::make('Tempi di produzione')->columns(fn () => ! static::isAdmin() ? 2 : 3)
                    // ->hidden(fn(Get $get) => !Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin'))
                    ->schema([
                        DateTimePicker::make('start_at')
                            ->label('Inizio lavorazione')
                            ->seconds(false)
                            ->readOnly(fn () => ! static::isAdmin()),
                        DateTimePicker::make('end_at')
                            ->label('Fine lavorazione')
                            ->after('start_at')
                            ->seconds(false)
                            ->readOnly(fn () => ! static::isAdmin()),
                        TextInput::make('total_minutes')
                            ->label('Totale tempo lavorazione (mm)')
                            ->hidden(fn () => ! static::isAdmin())
                            ->numeric(),
                    ]),
                Hidden::make('paused')->dehydrated(false),
                Actions::make([
                    Action::make('Pausa')
                        ->icon('heroicon-m-pause-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn (Get $get): bool => ! $get('paused') && $get('start_at') != null && $get('end_at') == null)
                        ->action(function (WorkOrder $record, EditRecord $livewire, WorkOrderTimerService $timer) {
                            $timer->pause($record);

                            return redirect($livewire->getResourceUrl('edit', ['record' => $record]));
                        }),
                    Action::make('Riprendi')
                        ->icon('heroicon-m-play-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Get $get): bool => (bool) $get('paused') && $get('end_at') == null)
                        ->action(function (WorkOrder $record, EditRecord $livewire, WorkOrderTimerService $timer) {
                            $timer->resume($record);

                            return redirect($livewire->getResourceUrl('edit', ['record' => $record]));
                        }),
                    Action::make('Fine Lavorazione')
                        ->icon('heroicon-m-clock')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->visible(fn (Get $get): bool => $get('start_at') != null && $get('end_at') == null)
                        ->disabled(fn (Get $get): bool => (bool) $get('paused'))
                        ->action(function (WorkOrder $record, EditRecord $livewire, WorkOrderTimerService $timer) {
                            $timer->finish($record);

                            return redirect($livewire->getResourceUrl('edit', ['record' => $record]));
                        }),
                ])
                    ->hidden(fn (Get $get): bool => $get('start_at') == null || $get('end_at') != null)
                    ->fullWidth(),
            ]);
    }

    protected static function currentUser(): ?User
    {
        /** @var User|null $user */
        $user = Auth::user();

        return $user;
    }

    protected static function isAdmin(): bool
    {
        $user = static::currentUser();

        return $user !== null && $user->hasRole(['admin', 'super_admin']);
    }
}

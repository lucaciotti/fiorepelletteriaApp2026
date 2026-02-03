<?php

namespace App\Filament\Resources\WorkOrders\Schemas;

use App\Models\Order;
use App\Models\OrderRow;
use App\Models\ProcessType;
use DateTime;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Auth;
use Carbon\Carbon;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;

class WorkOrderForm
{
    public static function configure(Schema $schema): Schema
    {
        $operator_id = Auth::user()->operator_id;
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
                        ->options(fn(Get $get) => OrderRow::where('closed', false)->where('order_id', $get('order_id'))->get()->pluck('product.code', 'id'))
                        ->live()
                        ->required(),
                ]),
                Fieldset::make('Dati produzione')->columns(fn(Get $get) => $get('quantity') > 0 ? 3 : 2)
                ->schema([
                    Select::make('operator_id')
                        ->default($operator_id)
                        ->label('Operatore Lavorazione')
                        ->disabled(fn(Get $get) => !Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin'))
                        ->relationship('operator', 'name')
                        ->required(),
                    Select::make('process_type_id')
                        ->label('Tipo Lavorazione')
                        ->options(ProcessType::all()->pluck('full_descr', 'id'))
                        // ->relationship('processType', 'full_descr')
                        ->searchable()
                        ->preload()
                        ->required(),
                    TextInput::make('quantity')->label('Quantità')
                        ->visible(fn(Get $get) => $get('quantity')>0)
                        ->required()
                        ->numeric(),
                ]),
                Fieldset::make('Tempi di produzione')->columns(fn(Get $get) => (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin')) ? 2 : 3)
                    // ->hidden(fn(Get $get) => !Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin'))
                    ->schema([
                        DateTimePicker::make('start_at')
                            ->label('Inizio lavorazione')
                            ->seconds(false)
                            ->readOnly((fn(Get $get) => !Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin'))),
                        DateTimePicker::make('end_at')
                            ->label('Fine lavorazione')
                            ->after('start_at')
                            ->seconds(false)
                            ->readOnly(fn(Get $get) => !Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin')),
                        TextInput::make('total_minutes')
                            ->label('Totale tempo lavorazione (mm)')
                            ->hidden(fn(Get $get) => !Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin'))
                            ->numeric(),
                    ]),
                Section::make('Registro tempi lavorazione')->columns(1)->collapsed()->collapsible()
                    // ->hidden(fn(Get $get) => !Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin'))
                    ->schema([
                        Repeater::make('recordsTime')->label('Registro tempi lavorazione')->columns(2)->deletable(false)->addable(false)->reorderable(false)->relationship()
                            ->schema([
                                DateTimePicker::make('start_at')
                                    ->label('Ora inizio lavorazione')
                                    ->seconds(false)
                                    ->readOnly((fn(Get $get) => !Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin'))),
                                DateTimePicker::make('end_at')
                                    ->label('Ora fine lavorazione')
                                    ->seconds(false)
                                    ->readOnly(fn(Get $get) => !Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin')),
                            ])
                            ->mutateRelationshipDataBeforeSaveUsing(function (array $data): array {
                                if ($data['end_at'] != null) {
                                    $data['total_minutes'] = round(Carbon::createFromDate($data['start_at'])->diffInMinutes(Carbon::createFromDate($data['end_at'])), 0);
                                }
                                return $data;
                            }),
                    ]),
                Hidden::make('paused'),
                Actions::make([
                    // Action::make('Inizio Lavorazione')
                    //     ->icon('heroicon-m-clock')
                    //     ->color('success')
                    //     ->requiresConfirmation()
                    //     ->disabled(fn(Get $get) => $get('start_at') !== null)
                    //     ->action(function (Set $set, Get $get , $state) {
                    //         $set('start_at', now());
                    //         $records = $get('recordsTime');
                    //         end($records);         // move the internal pointer to the end of the array
                    //         $key = key($records);                
                    //         end($records);         // move the internal pointer to the end of the array
                    //         $key = key($records);
                    //         if(count($records)==0){
                    //             $newRec = [];
                    //             $newRec['start_at'] = null;
                    //             $newRec['end_at'] = null;
                    //             array_push($records, $newRec);
                    //             end($records);         // move the internal pointer to the end of the array
                    //             $key = key($records);
                    //         }
                    //         $records[$key]['start_at'] = now()->format('Y-m-d H:i');
                    //         // dd($records);
                    //         $set('recordsTime', $records);
                    //     }),
                    Action::make('Pausa')
                        ->icon('heroicon-m-pause-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn(Get $get) => !$get('paused'))
                        ->disabled(fn(Get $get) => $get('end_at') != null)
                        ->action(function (Set $set, Get $get, $state, EditRecord $livewire) {
                            $records = $get('recordsTime');
                            end($records);         // move the internal pointer to the end of the array
                            $key = key($records);
                            if(count($records)==1){
                                $records[$key]['start_at'] = $get('start_at');
                            }
                            $records[$key]['end_at'] = now()->format('Y-m-d H:i');
                            $newRec = last($records);
                            $newRec['start_at'] = null;
                            $newRec['end_at'] = null;
                            array_push($records, $newRec);
;                            // dd($records);
                            $set('recordsTime', $records);
                            $set('paused', true);
                            // dd($get('recordsTime'));
                            $livewire->save();
                        }),
                    Action::make('Riprendi')
                        ->icon('heroicon-m-play-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn(Get $get) => $get('paused'))
                        ->disabled(fn(Get $get) => $get('end_at')!=null)
                        ->action(function (Set $set, Get $get, $state, EditRecord $livewire) {
                            $records = $get('recordsTime');
                            end($records);         // move the internal pointer to the end of the array
                            $key = key($records);
                            if (!empty($records[$key]['start_at'])){
                                $newRec = last($records);
                                $newRec['start_at'] = null;
                                $newRec['end_at'] = null;
                                array_push($records, $newRec);
                                end($records);         // move the internal pointer to the end of the array
                                $key = key($records);
                            }
                            $records[$key]['start_at'] = now()->format('Y-m-d H:i');
                            // dd($records);
                            $set('recordsTime', $records);
                            $set('paused', false);
                            // dd($get('recordsTime'));
                            $livewire->save();
                        }),
                    Action::make('Fine Lavorazione')
                        ->icon('heroicon-m-clock')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->disabled(fn(Get $get) => $get('start_at')==null || $get('paused') == true)
                        ->schema([
                            TextInput::make('quantity')->label('Quantità')
                                ->required()
                                ->numeric(),
                        ])
                        ->action(function (array $data, Set $set, Get $get, $state, EditRecord $livewire) {
                            $set('quantity', $data['quantity']);
                            $set('end_at', now());
                            $records = $get('recordsTime');
                            end($records);         // move the internal pointer to the end of the array
                            $key = key($records);
                            if (count($records) == 1) {
                                $records[$key]['start_at'] = $get('start_at');
                            }
                            $records[$key]['end_at'] = now()->format('Y-m-d H:i');
;                            // dd($records);
                            $set('recordsTime', $records);
                            $livewire->save();
                            // $this->saveOrCreate();
                        }),
                ])
                ->hidden(fn(Get $get) => $get('start_at') == null || $get('end_at') != null)->fullWidth(),
            ]);
    }
}

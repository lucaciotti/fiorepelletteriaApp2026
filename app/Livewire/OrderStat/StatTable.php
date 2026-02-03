<?php

namespace App\Livewire\OrderStat;

use App\Filament\Resources\WorkOrders\WorkOrderResource;
use App\Models\Customer;
use App\Models\Operator;
use App\Models\ProcessType;
use App\Models\Product;
use App\Models\WorkOrder;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Actions\ExportBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\Summarizers\Summarizer;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Malzariey\FilamentDaterangepickerFilter\Filters\DateRangeFilter;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Session;
use Str;

class StatTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithTable;
    use InteractsWithSchemas;

    protected $listeners = [
        'tableRefresh' => '$refresh',
    ];

    public function table(Table $table): Table
    {
        $groupType = '';
        if (Session::has('orderstat.form.groupType')) {
            $groupType = Session::get('orderstat.form.groupType') ?? '';
        }
        $originalGroupColumns = explode('-', $groupType);

        $records = $this->_buildRecords($originalGroupColumns);
        $columns = $this->_buildColumns($originalGroupColumns);
        
        $table
        ->records(fn(): Collection => $records)
        ->recordClasses(fn($record) => match ($record['lvl']) {
            1 => 'row-group-lvl-1',
            2 => 'row-group-lvl-2',
            3 => 'row-group-lvl-3',
            4 => 'row-group-lvl-4',
            5 => 'row-group-lvl-5',
            default => 'row-group-lvl-99',
        })
        ->columns($columns)
        ->deferColumnManager(false)
        ->filters([
        ], layout: FiltersLayout::Modal)->filtersTriggerAction(
            fn(Action $action) => $action
                ->button()
                ->slideOver()
                ->label(__('Filter')),
        )
        ->deferFilters(false)
        ->headerActions([
        ])
        ->recordActions([
        ])
        ->toolbarActions([
            // ExportAction::make()->exports([
            //     ExcelExport::make('table')->fromTable()->only([
            //         'customer',
            //     ])->ignoreFormatting()->modifyQueryUsing(fn($query) => dd($query)),
            // ]),
        ]);

        return $table;
    }

    public function render(): View
    {
        return view('livewire.order-stat.stat-table');
    }

    protected function _buildRecords($originalGroupColumns): Collection
    {
        $groupColumns = $originalGroupColumns;
        $records = [];
        while (count($groupColumns) > 0) {
            $records = array_merge($records, $this->_recordGroupBuilder($groupColumns, $originalGroupColumns));
            $groupColumns = array_slice($groupColumns, 0, -1);
        }
        $data = collect($records)->sortBy(array_merge($originalGroupColumns, ['lvl']));
        $data_reverse = $data->reverse();
        $last_level='';
        $avg_minutes=0;

        $total_avg_minutes=[];
        for ($i=count($originalGroupColumns); $i > 0 ; $i--) {
            $total_avg_minutes[$i] = 0;
        }
        foreach ($data_reverse as $pos => $row) {
            // if($row['lvl'] != $last_level){
            //     if (!array_key_exists($row['lvl'], $total_avg_minutes)){
            //         $total_avg_minutes[$row['lvl']] = 0;
            //     }
            //     if (intval($last_level)> intval($row['lvl'])){
            //         $total_avg_minutes[$row['lvl']] += $total_avg_minutes[$last_level];
            //         $total_avg_minutes[$last_level] = 0;
            //     }
            //     $last_level = $row['lvl'];
            // }
            if($row['lvl']==99){
                $avg_minutes = round($row['total_minutes'] / $row['quantity'], 2);
                $row['avg_minutes'] = $avg_minutes;
                for ($i = count($originalGroupColumns); $i > 0; $i--) {
                    $total_avg_minutes[$i] += $avg_minutes;
                }
            } else {
                // dd($total_avg_minutes);
                $row['avg_minutes'] = $total_avg_minutes[$row['lvl']];
                $total_avg_minutes[$row['lvl']] = 0;
            }
            $data_reverse[$pos] = $row;
        }
        // dd($data_reverse);
        return $data_reverse->reverse();
    }

    protected function _recordGroupBuilder($groupColumns, $originalGroupColumns): array
    {

        $products = Session::get('orderstat.form.filter.products') ?? [];
        $customers = Session::get('orderstat.form.filter.customers') ?? [];
        $operators = Session::get('orderstat.form.filter.operators') ?? [];

        $lvl = (count($groupColumns) == count($originalGroupColumns)) ? 99 : count($groupColumns);
        $groupColumns = array_map(fn($v) => $v == 'order_id' ? 'work_orders.order_id' : $v, $groupColumns);
        // $records = WorkOrder::selectRaw(implode(', ', $groupColumns) . ', ' . $lvl . ' as lvl, SUM(quantity) as quantity, SUM(total_minutes) as total_minutes, MIN(created_at) as created_at, MAX(end_at) as end_at')
        if ($lvl==99){
            $selectRaw = implode(', ', $groupColumns) . ', ' . $lvl . ' as lvl, SUM(order_rows.quantity) as quantity, SUM(total_minutes) as total_minutes, 0 as avg_minutes, MIN(work_orders.created_at) as created_at, MAX(work_orders.end_at) as end_at';
        } else {
            $selectRaw = implode(', ', $groupColumns) . ', ' . $lvl . ' as lvl, 0 as quantity, 0 as total_minutes, 0 as avg_minutes, MIN(work_orders.created_at) as created_at, MAX(work_orders.end_at) as end_at';
        }
        if (in_array("work_orders.order_id", $groupColumns)){
            $selectRaw .= ', MAX(orders.number) as number';
        }

        $records =  DB::table('work_orders')
            ->leftjoin('orders', 'orders.id', '=', 'work_orders.order_id')
            ->leftjoin('customers', 'customers.id', '=', 'orders.customer_id')
            ->leftjoin('order_rows', 'order_rows.id', '=', 'work_orders.order_row_id')
            ->leftjoin('products', 'products.id', '=', 'order_rows.product_id')
            ->selectRaw($selectRaw)
            ->where('end_at', '!=', null);
        if (!empty($products)){
            $records->whereIn('product_id', $products);
        }
        if (!empty($customers)){
            $records->whereIn('customer_id', $customers);
        }
        if (!empty($operators)){
            $records->whereIn('operator_id', $operators);
        }
        $records = $records->groupBy($groupColumns)
            ->get();
        // ->toArray();

        $data = collect($records)->map(function ($x) {
            return (array) $x;
        })->toArray();

        // if(count($groupColumns) != count($originalGroupColumns)){
        //     dd($data);
        // }
        return $data;
    }

    protected function _buildColumns($originalGroupColumns): array
    {
        $groupColumns = $originalGroupColumns;
        $columnsTitle = [];
        $columnsGroup = [];
        $columnsAlways = [
            TextColumn::make('quantity')->label('Qta')
                ->state(function ($record) {
                    return $record['quantity']>0 ? $record['quantity'] : '';
                })
                ->numeric(),
            // ->state(function ($record) {
            //     return $record['lvl']==99 ? $record['quantity'] : '';
            // }),
            TextColumn::make('total_minutes')->label('Totale Minuti')
                ->state(function ($record) {
                    return $record['total_minutes'] > 0 ? $record['total_minutes'] : '';
                })
                ->numeric(),
                TextColumn::make('avg_minutes')->label('Media Minuti / Pz')
                ->numeric()
                ->state(function ($record) {
                    return $record['avg_minutes'] > 0 ? $record['avg_minutes'] : '';
                })
                ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('avg_minutes_trsl')->label('Media / Pz [h:m:s]')
                ->state(function ($record) {
                    return $record['avg_minutes'] > 0 ? sprintf('%02d:%02d:%02d', floor($record['avg_minutes'] / 60), $record['avg_minutes'] % 60,
                    ($record['avg_minutes']-floor($record['avg_minutes']))*60) : '';
                }),
                // ->state(function ($record) {
                //     return $record['total_minutes'] ? round($record['total_minutes'] / $record['quantity'], 2) : 0;
                // }),
            // TextColumn::make('created_at')->label('Data Creazione')
            //     ->dateTime()
            //     ->toggleable(isToggledHiddenByDefault: true),
        ];
        $columnGroupMapTitle = [];

        $state = '';
        $totCol = count($groupColumns);
        while (count($groupColumns) > 0) {
            if (!empty($state)) $state = $state . ' -> ';
            switch ($groupColumns[0]) {
                case 'process_type_id':
                    $state = $state . 'Lavorazione';
                    break;
                case 'customer_id':
                    $state = $state . 'Cliente';
                    break;
                case 'operator_id':
                    $state = $state . 'Operatore';
                    break;
                case 'product_id':
                    $state = $state . 'Prodotto';
                    break;
                case 'order_id':
                    $state = $state . 'n.Ord.';
                    break;
                default:
                    $state = $state . $groupColumns[0];
                    break;
            }
            $columnGroupMapTitle[$totCol - count($groupColumns) + 1] = $state;
            $groupColumns = array_slice($groupColumns, 1);
        }

        $columnsTitle = [
            TextColumn::make('raggruppamento')
                ->state(function ($record) use ($columnGroupMapTitle) {
                    return $record['lvl'] != 99 ? $columnGroupMapTitle[$record['lvl']] : '';
                })
                ->toggleable(isToggledHiddenByDefault: true),
        ];

        foreach ($originalGroupColumns as $value) {
            switch ($value) {
                case 'order_id':
                    array_push($columnsGroup, TextColumn::make('number')->label('n.Ord.')
                        ->url(fn($record): string => WorkOrderResource::getUrl(
                            'index',
                            [
                                'filters' => [
                                    'customer' => [
                                        'value' => $record['customer_id'] ?? null,
                                    ],
                                    'operator' => [
                                        'value' => $record['operator_id'] ?? null,
                                    ],
                                    'product' => [
                                        'value' => $record['product_id'] ?? null,
                                    ],
                                    'process_type_id' => [
                                        'value' => $record['process_type_id'] ?? null,
                                    ],
                                    'order' => [
                                        'value' => $record['order_id'] ?? null,
                                    ],
                                ],
                            ],
                            panel: 'app',
                        ), true));
                    break;
                case 'process_type_id':
                    array_push($columnsGroup, TextColumn::make('processType')->label('Lavorazione')
                        ->url(fn($record): string => WorkOrderResource::getUrl(
                            'index',
                            [
                                'filters' => [
                                    'customer' => [
                                        'value' => $record['customer_id']??null,
                                    ],
                                    'operator' => [
                                        'value' => $record['operator_id']??null,
                                    ],
                                    'product' => [
                                        'value' => $record['product_id']??null,
                                    ],
                                    'process_type_id' => [
                                        'value' => $record['process_type_id']??null,
                                    ],
                                    'order' => [
                                        'value' => $record['order_id']??null,
                                    ],
                                ],
                            ],
                            panel: 'app',
                        ), true)
                        ->state(function ($record) {
                            return ProcessType::find($record['process_type_id'] ?? null)->description ?? '';
                        }));
                    break;
                case 'customer_id':
                    array_push($columnsGroup, TextColumn::make('customer')->label('Cliente')
                        ->url(fn($record): string => WorkOrderResource::getUrl(
                            'index',
                            [
                                'filters' => [
                                    'customer' => [
                                        'value' => $record['customer_id'] ?? null,
                                    ],
                                    'operator' => [
                                        'value' => $record['operator_id'] ?? null,
                                    ],
                                    'product' => [
                                        'value' => $record['product_id'] ?? null,
                                    ],
                                    'process_type_id' => [
                                        'value' => $record['process_type_id'] ?? null,
                                    ],
                                    'order' => [
                                        'value' => $record['order_id'] ?? null,
                                    ],
                                ],
                            ],
                            panel: 'app',
                        ), true)
                        ->state(function ($record) {
                            return Customer::find($record['customer_id'] ?? null)->name ?? '';
                        }));
                    break;
                case 'operator_id':
                    array_push($columnsGroup, TextColumn::make('operator')->label('Operatore')
                        ->url(fn($record): string => WorkOrderResource::getUrl(
                            'index',
                            [
                                'filters' => [
                                    'customer' => [
                                        'value' => $record['customer_id'] ?? null,
                                    ],
                                    'operator' => [
                                        'value' => $record['operator_id'] ?? null,
                                    ],
                                    'product' => [
                                        'value' => $record['product_id'] ?? null,
                                    ],
                                    'process_type_id' => [
                                        'value' => $record['process_type_id'] ?? null,
                                    ],
                                    'order' => [
                                        'value' => $record['order_id'] ?? null,
                                    ],
                                ],
                            ],
                            panel: 'app',
                        ), true)
                        ->state(function ($record) {
                            return Operator::find($record['operator_id'] ?? null)->name ?? '';
                        }));
                    break;
                case 'product_id':
                    array_push($columnsGroup, TextColumn::make('product')->label('Prodotto')
                        ->url(fn($record): string => WorkOrderResource::getUrl(
                            'index',
                            [
                                'filters' => [
                                    'customer' => [
                                        'value' => $record['customer_id'] ?? null,
                                    ],
                                    'operator' => [
                                        'value' => $record['operator_id'] ?? null,
                                    ],
                                    'product' => [
                                        'value' => $record['product_id'] ?? null,
                                    ],
                                    'process_type_id' => [
                                        'value' => $record['process_type_id'] ?? null,
                                    ],
                                    'order' => [
                                        'value' => $record['order_id'] ?? null,
                                    ],
                                ],
                            ],
                            panel: 'app',
                        ), true)
                        ->state(function ($record) {
                            return Product::find($record['product_id'] ?? null)->code ?? '';
                        }));
                    break;

                default:
                    # code...
                    break;
            }
        }

        return array_merge($columnsTitle, $columnsGroup, $columnsAlways);
    }
}

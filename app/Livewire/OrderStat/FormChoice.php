<?php

namespace App\Livewire\OrderStat;

use App\Filament\Pages\OrderStat;
use App\Models\Customer;
use App\Models\Operator;
use App\Models\Product;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Session;

class FormChoice extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public ?array $data = [];

    public ?string $groupType = null;
    public ?array $products = [];
    public ?array $customers = [];
    public ?array $operators = [];

    public function mount(): void
    {
        if (!Session::has('orderstat.form.groupType')) {
            Session::put('orderstat.form.groupType', 'customer_id-order_id-product_id-process_type_id');
        }
        if (Session::has('orderstat.form.groupType') && Session::get('orderstat.form.groupType') == 'customer_id-number-product_id-process_type_id') {
            Session::put('orderstat.form.groupType', 'customer_id-order_id-product_id-process_type_id');
        }
        $this->groupType = Session::get('orderstat.form.groupType');
        $this->products = Session::get('orderstat.form.filter.products') ?? [];
        $this->customers = Session::get('orderstat.form.filter.customers') ?? [];
        $this->operators = Session::get('orderstat.form.filter.operators') ?? [];
        $this->form->fill([
            'groupType'=> $this->groupType,
            'products'=> $this->products,
            'customers'=> $this->customers,
            'operators'=> $this->operators,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema->columns(2)
            ->components([
                Section::make('Raggruppamento')
                ->schema([
                    Select::make('groupType')
                    ->hiddenLabel(true)
                    ->options([
                        // 'customer_id-number-product_id-process_type_id-operator_id' => 'Cliente -> n.Ord. -> Prodotto -> Lavorazioni -> Operatore',
                        'customer_id-order_id-product_id-process_type_id' => 'Cliente -> n.Ord. -> Prodotto -> Lavorazioni ',
                        // 'customer_id-number-product_id' => 'Cliente -> n.Ord. -> Prodotto',
                        // 'customer_id-number' => 'Cliente -> n.Ord.',
                        // 'product_id-process_type_id-operator_id' => 'Prodotto -> Lavorazioni -> Operatore',
                        'product_id-process_type_id' => 'Prodotto -> Lavorazioni ',
                    ])
                    ->live(onBlur: true),
                ]),
                Section::make('Filtri')->collapsible()->collapsed()
                ->columns(1)
                ->schema([
                    Select::make('products')->label('Prodotti')
                    ->multiple()
                    ->options(Product::query()->pluck('code', 'id'))
                    ->searchable()
                    ->live(onBlur: true),
                    Select::make('customers')->label('Cliente')
                    ->multiple()
                    ->options(Customer::query()->pluck('name', 'id'))
                    ->searchable()
                    ->live(onBlur: true),
                    Select::make('operators')->label('Operatore')
                    ->multiple()
                    ->options(Operator::query()->pluck('name', 'id'))
                    ->searchable()
                    ->live(onBlur: true),
                ]),
            ]);
            // ->statePath('data');
    }

    public function submit(): void
    {
        $data = $this->form->getState();

        //
    }

    public function updatedGroupType()
    {
        Session::put('orderstat.form.groupType', $this->groupType);
        // dd(Session::get('orderstat.form.groupType'));
        $this->dispatch('tableRefresh');
    }

    public function updatedProducts()
    {
        Session::put('orderstat.form.filter.products', $this->products);
        // dd(Session::get('orderstat.form.groupType'));
        $this->dispatch('tableRefresh');
    }

    public function updatedCustomers()
    {
        Session::put('orderstat.form.filter.customers', $this->customers);
        // dd(Session::get('orderstat.form.groupType'));
        $this->dispatch('tableRefresh');
    }

    public function updatedOperators()
    {
        Session::put('orderstat.form.filter.operators', $this->operators);
        // dd(Session::get('orderstat.form.groupType'));
        $this->dispatch('tableRefresh');
    }

    public function render(): View
    {
        return view('livewire.order-stat.form-choice');
    }
}

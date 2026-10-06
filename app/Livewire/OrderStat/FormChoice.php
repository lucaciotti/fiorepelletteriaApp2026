<?php

namespace App\Livewire\OrderStat;

use App\Models\Customer;
use App\Models\Operator;
use App\Models\Product;
use App\Statistics\OrderStatState;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Schema;
use Illuminate\Contracts\View\View;
use Livewire\Component;

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
        $this->groupType = OrderStatState::groupType();

        $filters = OrderStatState::filters();
        $this->products = $filters['products'];
        $this->customers = $filters['customers'];
        $this->operators = $filters['operators'];

        $this->form->fill([
            'groupType' => $this->groupType,
            'products' => $this->products,
            'customers' => $this->customers,
            'operators' => $this->operators,
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
        OrderStatState::setGroupType($this->groupType);
        $this->dispatch('tableRefresh');
    }

    public function updatedProducts()
    {
        OrderStatState::setFilter('products', $this->products ?? []);
        $this->dispatch('tableRefresh');
    }

    public function updatedCustomers()
    {
        OrderStatState::setFilter('customers', $this->customers ?? []);
        $this->dispatch('tableRefresh');
    }

    public function updatedOperators()
    {
        OrderStatState::setFilter('operators', $this->operators ?? []);
        $this->dispatch('tableRefresh');
    }

    public function render(): View
    {
        return view('livewire.order-stat.form-choice');
    }
}

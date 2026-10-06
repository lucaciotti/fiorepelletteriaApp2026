<?php

use App\Models\Customer;
use App\Models\Operator;
use App\Models\Order;
use App\Models\OrderRow;
use App\Models\ProcessType;
use App\Models\Product;
use App\Models\WorkOrder;
use App\Statistics\WorkOrderStatsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/**
 * Crea un contesto condiviso (cliente/prodotto/operatore/ordine/lavorazione).
 *
 * @return array{0: Customer, 1: Product, 2: Operator, 3: Order, 4: ProcessType}
 */
function makeStatsContext(): array
{
    $customer = Customer::create(['name' => 'Cliente Stats']);
    $product = Product::create(['code' => 'SP1', 'description' => 'Prodotto Stats']);
    $operator = Operator::create(['name' => 'Operatore Stats', 'description' => 'Op']);
    $order = Order::create(['number' => 'S1', 'customer_id' => $customer->id]);
    $processType = ProcessType::create(['description' => 'Taglio Stats']);

    return [$customer, $product, $operator, $order, $processType];
}

/**
 * @param  array{0: Customer, 1: Product, 2: Operator, 3: Order, 4: ProcessType}  $context
 */
function makeStatsWorkOrder(array $context, float $quantity, float $minutes, array $overrides = []): WorkOrder
{
    [$customer, $product, $operator, $order, $processType] = $context;

    $orderRow = OrderRow::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => $quantity,
    ]);

    return WorkOrder::create(array_merge([
        'operator_id' => $operator->id,
        'order_id' => $order->id,
        'order_row_id' => $orderRow->id,
        'process_type_id' => $processType->id,
        'start_at' => Carbon::parse('2026-01-01 08:00:00'),
        'end_at' => Carbon::parse('2026-01-01 10:00:00'),
        'total_minutes' => $minutes,
    ], $overrides));
}

it('aggrega a livello foglia quantità e minuti', function () {
    $context = makeStatsContext();
    makeStatsWorkOrder($context, 10, 120);
    makeStatsWorkOrder($context, 5, 60);

    $rows = app(WorkOrderStatsQuery::class)->aggregate(
        columns: ['customer_id'],
        originalGroupColumns: ['customer_id'],
    );

    expect($rows)->toHaveCount(1)
        ->and((int) $rows[0]['lvl'])->toBe(99)
        ->and((float) $rows[0]['total_minutes'])->toBe(180.0)
        ->and((float) $rows[0]['quantity'])->toBe(10.0);
});

it('i livelli aggregati non riportano quantità né minuti', function () {
    $context = makeStatsContext();
    makeStatsWorkOrder($context, 10, 120);

    $rows = app(WorkOrderStatsQuery::class)->aggregate(
        columns: ['customer_id'],
        originalGroupColumns: ['customer_id', 'product_id'],
    );

    expect($rows)->toHaveCount(1)
        ->and((int) $rows[0]['lvl'])->toBe(1)
        ->and((float) $rows[0]['total_minutes'])->toBe(0.0)
        ->and((float) $rows[0]['quantity'])->toBe(0.0);
});

it('esclude le lavorazioni non terminate', function () {
    $context = makeStatsContext();
    makeStatsWorkOrder($context, 10, 120, ['end_at' => null]);

    $rows = app(WorkOrderStatsQuery::class)->aggregate(
        columns: ['customer_id'],
        originalGroupColumns: ['customer_id'],
    );

    expect($rows)->toBeEmpty();
});

it('filtra per cliente, prodotto e operatore', function () {
    $contextA = makeStatsContext();
    $workOrderA = makeStatsWorkOrder($contextA, 10, 120);

    $contextB = makeStatsContext();
    makeStatsWorkOrder($contextB, 5, 60);

    $query = app(WorkOrderStatsQuery::class);

    expect($query->aggregate(['customer_id'], ['customer_id'], [
        'customers' => [$workOrderA->order->customer_id],
    ]))->toHaveCount(1);

    expect($query->aggregate(['customer_id'], ['customer_id'], [
        'operators' => [$workOrderA->operator_id],
    ]))->toHaveCount(1);

    expect($query->aggregate(['customer_id'], ['customer_id'], [
        'products' => [$workOrderA->orderRow->product_id],
    ]))->toHaveCount(1);
});

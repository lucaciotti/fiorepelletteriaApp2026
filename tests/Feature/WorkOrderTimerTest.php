<?php

use App\Models\Customer;
use App\Models\Operator;
use App\Models\Order;
use App\Models\OrderRow;
use App\Models\ProcessType;
use App\Models\Product;
use App\Models\WorkOrder;
use App\Models\WorkOrdersRecordTime;
use App\Services\RecalculateWorkOrderTime;
use App\Services\WorkOrderTimerService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function makeTimerWorkOrder(array $attributes = []): WorkOrder
{
    $customer = Customer::create(['name' => 'Cliente Test']);
    $product = Product::create(['code' => 'P1', 'description' => 'Prodotto Test']);
    $operator = Operator::create(['name' => 'Operatore Test', 'description' => 'Op Test']);
    $order = Order::create(['number' => '1', 'customer_id' => $customer->id]);
    $orderRow = OrderRow::create([
        'order_id' => $order->id,
        'product_id' => $product->id,
        'quantity' => 10,
    ]);
    $processType = ProcessType::create(['description' => 'Cucitura Test']);

    return WorkOrder::create(array_merge([
        'operator_id' => $operator->id,
        'order_id' => $order->id,
        'order_row_id' => $orderRow->id,
        'process_type_id' => $processType->id,
    ], $attributes));
}

function createTimerSegment(
    WorkOrder $workOrder,
    ?string $start,
    ?string $end,
    string $createdAt,
    string $updatedAt,
): WorkOrdersRecordTime {
    $segment = WorkOrdersRecordTime::create([
        'work_order_id' => $workOrder->id,
        'start_at' => $start,
        'end_at' => $end,
    ]);

    DB::table('work_orders_record_time')->where('id', $segment->id)->update([
        'created_at' => $createdAt,
        'updated_at' => $updatedAt,
    ]);

    return $segment->refresh();
}

it('avvia la lavorazione aprendo un segmento', function () {
    $timer = app(WorkOrderTimerService::class);
    $workOrder = makeTimerWorkOrder();

    $timer->start($workOrder, Carbon::parse('2026-10-01 08:00:00'));

    $workOrder->refresh();

    expect($workOrder->recordsTime()->count())->toBe(1)
        ->and($workOrder->recordsTime()->whereNull('end_at')->count())->toBe(1)
        ->and($workOrder->paused)->toBeFalse()
        ->and($workOrder->start_at->format('Y-m-d H:i'))->toBe('2026-10-01 08:00');
});

it('mette in pausa chiudendo i segmenti e impostando paused', function () {
    $timer = app(WorkOrderTimerService::class);
    $workOrder = makeTimerWorkOrder();
    $t0 = Carbon::parse('2026-10-01 08:00:00');

    $timer->start($workOrder, $t0);
    $timer->pause($workOrder->fresh(), (clone $t0)->addMinutes(10));

    $workOrder->refresh();

    expect($workOrder->recordsTime()->whereNull('end_at')->count())->toBe(0)
        ->and($workOrder->paused)->toBeTrue()
        ->and((float) $workOrder->total_minutes)->toBe(10.0);
});

it('riprende aprendo un nuovo segmento', function () {
    $timer = app(WorkOrderTimerService::class);
    $workOrder = makeTimerWorkOrder();
    $t0 = Carbon::parse('2026-10-01 08:00:00');

    $timer->start($workOrder, $t0);
    $timer->pause($workOrder->fresh(), (clone $t0)->addMinutes(10));
    $timer->resume($workOrder->fresh(), (clone $t0)->addMinutes(20));

    $workOrder->refresh();

    expect($workOrder->recordsTime()->count())->toBe(2)
        ->and($workOrder->recordsTime()->whereNull('end_at')->count())->toBe(1)
        ->and($workOrder->paused)->toBeFalse();
});

it('conclude la lavorazione calcolando i minuti totali', function () {
    $timer = app(WorkOrderTimerService::class);
    $workOrder = makeTimerWorkOrder();
    $t0 = Carbon::parse('2026-10-01 08:00:00');

    $timer->start($workOrder, $t0);
    $timer->pause($workOrder->fresh(), (clone $t0)->addMinutes(10));
    $timer->resume($workOrder->fresh(), (clone $t0)->addMinutes(20));
    $timer->finish($workOrder->fresh(), (clone $t0)->addMinutes(30));

    $workOrder->refresh();

    expect($workOrder->recordsTime()->whereNull('end_at')->count())->toBe(0)
        ->and($workOrder->end_at)->not->toBeNull()
        ->and($workOrder->paused)->toBeFalse()
        ->and((float) $workOrder->total_minutes)->toBe(20.0)
        ->and($workOrder->recordsTime()->sum('total_minutes'))->toBe(20.0);
});

it('è idempotente su doppia pausa e doppia conclusione', function () {
    $timer = app(WorkOrderTimerService::class);
    $workOrder = makeTimerWorkOrder();
    $t0 = Carbon::parse('2026-10-01 08:00:00');

    $timer->start($workOrder, $t0);
    $timer->pause($workOrder->fresh(), (clone $t0)->addMinutes(5));
    $timer->pause($workOrder->fresh(), (clone $t0)->addMinutes(8));
    $timer->finish($workOrder->fresh(), (clone $t0)->addMinutes(10));
    $timer->finish($workOrder->fresh(), (clone $t0)->addMinutes(15));

    $workOrder->refresh();

    expect($workOrder->recordsTime()->whereNull('end_at')->count())->toBe(0)
        ->and($workOrder->recordsTime()->count())->toBe(1)
        ->and($workOrder->paused)->toBeFalse();
});

it('conclude una lavorazione con registro vuoto', function () {
    $timer = app(WorkOrderTimerService::class);
    $workOrder = makeTimerWorkOrder(['start_at' => Carbon::parse('2026-10-01 08:00:00')]);

    $timer->finish($workOrder, Carbon::parse('2026-10-01 08:45:00'));

    $workOrder->refresh();

    expect($workOrder->recordsTime()->count())->toBe(1)
        ->and($workOrder->recordsTime()->whereNull('end_at')->count())->toBe(0)
        ->and((float) $workOrder->total_minutes)->toBe(45.0);
});

it('sincronizza i segmenti (crea, aggiorna, elimina) e ricalcola', function () {
    $timer = app(WorkOrderTimerService::class);
    $workOrder = makeTimerWorkOrder(['start_at' => Carbon::parse('2026-10-01 08:00:00')]);
    $timer->start($workOrder, Carbon::parse('2026-10-01 08:00:00'));

    $timer->syncSegments($workOrder->fresh(), [
        ['id' => null, 'start_at' => '2026-10-01 09:00:00', 'end_at' => '2026-10-01 09:15:00'],
        ['id' => null, 'start_at' => '2026-10-01 10:00:00', 'end_at' => '2026-10-01 10:30:00'],
    ]);

    // Il registro passato è autoritativo: sostituisce i segmenti esistenti.
    $workOrder->refresh();
    expect($workOrder->recordsTime()->count())->toBe(2)
        ->and((float) $workOrder->total_minutes)->toBe(45.0);

    $kept = $workOrder->recordsTime()->orderBy('id')->first();

    $timer->syncSegments($workOrder->fresh(), [
        ['id' => $kept->id, 'start_at' => '2026-10-01 09:00:00', 'end_at' => '2026-10-01 09:20:00'],
    ]);

    $workOrder->refresh();
    expect($workOrder->recordsTime()->count())->toBe(1)
        ->and((float) $workOrder->total_minutes)->toBe(20.0);
});

it('il metodo A non gonfia i tempi usando updated_at', function () {
    $recalc = app(RecalculateWorkOrderTime::class);
    $workOrder = makeTimerWorkOrder([
        'start_at' => Carbon::parse('2026-10-01 08:00:00'),
        'end_at' => Carbon::parse('2026-10-01 12:00:00'),
    ]);

    createTimerSegment($workOrder, '2026-10-01 08:00:00', null, '2026-10-01 08:00:00', '2026-10-01 12:00:00');
    createTimerSegment($workOrder, '2026-10-01 10:00:00', null, '2026-10-01 10:00:00', '2026-10-05 09:00:00');

    $proposalA = $recalc->methodA($workOrder->fresh());
    $proposalB = $recalc->methodB($workOrder->fresh());

    expect($proposalA['total'])->toBe(240.0)
        ->and($proposalB['total'])->toBeGreaterThan(240.0);
});

it('il metodo A tratta i segmenti senza start come durata zero', function () {
    $recalc = app(RecalculateWorkOrderTime::class);
    $workOrder = makeTimerWorkOrder([
        'start_at' => Carbon::parse('2026-10-01 08:00:00'),
        'end_at' => Carbon::parse('2026-10-01 10:00:00'),
    ]);

    createTimerSegment($workOrder, '2026-10-01 08:00:00', '2026-10-01 08:30:00', '2026-10-01 08:00:00', '2026-10-01 08:30:00');
    createTimerSegment($workOrder, null, null, '2026-10-01 08:30:00', '2026-10-01 08:30:00');

    $proposalA = $recalc->methodA($workOrder->fresh());

    expect($proposalA['total'])->toBe(30.0);
});

<?php

namespace App\Statistics;

use Illuminate\Support\Facades\DB;

/**
 * Query di aggregazione per le Statistiche Lavorazioni.
 *
 * Estratta dal componente Livewire per essere testabile in isolamento.
 * Il livello "foglia" (raggruppamento completo) porta quantità e minuti reali;
 * i livelli superiori sono aggregazioni parziali (lvl = numero di colonne).
 */
class WorkOrderStatsQuery
{
    /**
     * @param  array<int, string>  $columns  raggruppamento corrente (sottoinsieme)
     * @param  array<int, string>  $originalGroupColumns  raggruppamento completo
     * @param  array{products?: array<int, mixed>, customers?: array<int, mixed>, operators?: array<int, mixed>}  $filters
     * @return array<int, array<string, mixed>>
     */
    public function aggregate(array $columns, array $originalGroupColumns, array $filters = []): array
    {
        $level = count($columns) === count($originalGroupColumns) ? 99 : count($columns);

        $qualifiedColumns = array_map(
            fn (string $column): string => $column === 'order_id' ? 'work_orders.order_id' : $column,
            $columns
        );

        if ($level === 99) {
            $selectRaw = implode(', ', $qualifiedColumns).', '.$level.' as lvl, '
                .'MAX(order_rows.quantity) as quantity, SUM(total_minutes) as total_minutes, '
                .'0 as avg_minutes, MIN(work_orders.created_at) as created_at, MAX(work_orders.end_at) as end_at';
        } else {
            $selectRaw = implode(', ', $qualifiedColumns).', '.$level.' as lvl, '
                .'0 as quantity, 0 as total_minutes, 0 as avg_minutes, '
                .'MIN(work_orders.created_at) as created_at, MAX(work_orders.end_at) as end_at';
        }

        if (in_array('work_orders.order_id', $qualifiedColumns, true)) {
            $selectRaw .= ', MAX(orders.number) as number';
        }

        $query = DB::table('work_orders')
            ->leftJoin('orders', 'orders.id', '=', 'work_orders.order_id')
            ->leftJoin('customers', 'customers.id', '=', 'orders.customer_id')
            ->leftJoin('order_rows', 'order_rows.id', '=', 'work_orders.order_row_id')
            ->leftJoin('products', 'products.id', '=', 'order_rows.product_id')
            ->selectRaw($selectRaw)
            ->whereNotNull('work_orders.end_at');

        if (! empty($filters['products'])) {
            $query->whereIn('order_rows.product_id', $filters['products']);
        }

        if (! empty($filters['customers'])) {
            $query->whereIn('orders.customer_id', $filters['customers']);
        }

        if (! empty($filters['operators'])) {
            $query->whereIn('work_orders.operator_id', $filters['operators']);
        }

        return $query->groupBy($qualifiedColumns)
            ->get()
            ->map(fn ($row): array => (array) $row)
            ->all();
    }
}

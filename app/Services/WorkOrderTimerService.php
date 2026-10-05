<?php

namespace App\Services;

use App\Models\WorkOrder;
use App\Models\WorkOrdersRecordTime;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Unico proprietario della scrittura sui segmenti di tempo di una lavorazione.
 *
 * Tutte le transizioni di stato (avvio, pausa, ripresa, conclusione) passano da qui
 * e vengono eseguite in transazione, in modo idempotente e coerente con `total_minutes`.
 */
class WorkOrderTimerService
{
    /**
     * Avvia una lavorazione: imposta `start_at` se assente e apre un segmento.
     */
    public function start(WorkOrder $workOrder, ?CarbonInterface $at = null): WorkOrdersRecordTime
    {
        $at ??= $workOrder->start_at ? Carbon::parse($workOrder->start_at) : now();

        return DB::transaction(function () use ($workOrder, $at): WorkOrdersRecordTime {
            $workOrder->forceFill([
                'start_at' => $workOrder->start_at ?? $at,
                'end_at' => null,
                'paused' => false,
            ])->save();

            $open = $this->openSegments($workOrder);

            if ($open->isNotEmpty()) {
                return $open->first();
            }

            return WorkOrdersRecordTime::create([
                'work_order_id' => $workOrder->id,
                'start_at' => $at,
                'end_at' => null,
                'total_minutes' => null,
            ]);
        });
    }

    /**
     * Mette in pausa: chiude tutti i segmenti aperti e imposta `paused = true`.
     */
    public function pause(WorkOrder $workOrder, ?CarbonInterface $at = null): void
    {
        $at ??= now();

        DB::transaction(function () use ($workOrder, $at): void {
            $this->closeOpenSegments($workOrder, $at);

            $workOrder->forceFill(['paused' => true])->save();

            $this->recomputeTotalMinutes($workOrder);
        });
    }

    /**
     * Riprende: chiude eventuali segmenti residui e apre un nuovo segmento.
     */
    public function resume(WorkOrder $workOrder, ?CarbonInterface $at = null): WorkOrdersRecordTime
    {
        $at ??= now();

        return DB::transaction(function () use ($workOrder, $at): WorkOrdersRecordTime {
            // Idempotenza / riparazione: non devono restare segmenti aperti dopo la pausa.
            $this->closeOpenSegments($workOrder, $at);

            $workOrder->forceFill([
                'paused' => false,
                'end_at' => null,
            ])->save();

            return WorkOrdersRecordTime::create([
                'work_order_id' => $workOrder->id,
                'start_at' => $at,
                'end_at' => null,
                'total_minutes' => null,
            ]);
        });
    }

    /**
     * Conclude la lavorazione: chiude i segmenti aperti, imposta `end_at` e ricalcola i minuti.
     */
    public function finish(WorkOrder $workOrder, ?CarbonInterface $at = null): void
    {
        $at ??= now();

        DB::transaction(function () use ($workOrder, $at): void {
            // Se il registro è vuoto creiamo un unico segmento chiuso.
            if ($workOrder->recordsTime()->count() === 0) {
                WorkOrdersRecordTime::create([
                    'work_order_id' => $workOrder->id,
                    'start_at' => $workOrder->start_at ?? $at,
                    'end_at' => $at,
                    'total_minutes' => $this->minutesBetween($workOrder->start_at ?? $at, $at),
                ]);
            }

            $this->closeOpenSegments($workOrder, $at);

            $workOrder->forceFill([
                'end_at' => $at,
                'paused' => false,
            ])->save();

            $this->recomputeTotalMinutes($workOrder);
        });
    }

    /**
     * Riporta in coerenza una lavorazione dopo un salvataggio manuale dal form admin.
     */
    public function reconcile(WorkOrder $workOrder): void
    {
        DB::transaction(function () use ($workOrder): void {
            $workOrder->refresh();

            if ($workOrder->end_at !== null) {
                // Lavorazione chiusa: nessun segmento deve restare aperto.
                $this->closeOpenSegments($workOrder, Carbon::parse($workOrder->end_at));

                if ($workOrder->recordsTime()->count() === 0) {
                    WorkOrdersRecordTime::create([
                        'work_order_id' => $workOrder->id,
                        'start_at' => $workOrder->start_at ?? $workOrder->end_at,
                        'end_at' => $workOrder->end_at,
                        'total_minutes' => $this->minutesBetween($workOrder->start_at ?? $workOrder->end_at, $workOrder->end_at),
                    ]);
                }

                if ($workOrder->paused) {
                    $workOrder->forceFill(['paused' => false])->save();
                }
            } elseif ($workOrder->recordsTime()->count() === 0) {
                // Lavorazione in corso senza registro: apriamo il primo segmento.
                WorkOrdersRecordTime::create([
                    'work_order_id' => $workOrder->id,
                    'start_at' => $workOrder->start_at ?? now(),
                    'end_at' => null,
                    'total_minutes' => null,
                ]);
            }

            $this->recomputeTotalMinutes($workOrder);
        });
    }

    /**
     * Ricalcola `total_minutes` di ogni segmento chiuso e il totale della lavorazione.
     */
    public function recomputeTotalMinutes(WorkOrder $workOrder): float
    {
        return DB::transaction(function () use ($workOrder): float {
            $total = 0.0;

            foreach ($workOrder->recordsTime()->get() as $segment) {
                $minutes = $segment->end_at !== null
                    ? $this->minutesBetween($segment->start_at, $segment->end_at)
                    : 0.0;

                if (round((float) $segment->total_minutes, 2) !== $minutes) {
                    $segment->forceFill(['total_minutes' => $minutes])->save();
                }

                $total += $minutes;
            }

            $total = round($total, 2);

            $workOrder->forceFill(['total_minutes' => $total])->save();

            return $total;
        });
    }

    /**
     * @return \Illuminate\Support\Collection<int, WorkOrdersRecordTime>
     */
    protected function openSegments(WorkOrder $workOrder)
    {
        return $workOrder->recordsTime()
            ->whereNull('end_at')
            ->orderBy('id')
            ->get();
    }

    protected function closeOpenSegments(WorkOrder $workOrder, CarbonInterface $at): void
    {
        foreach ($this->openSegments($workOrder) as $segment) {
            $segment->forceFill([
                'end_at' => $at,
                'total_minutes' => $this->minutesBetween($segment->start_at, $at),
            ])->save();
        }
    }

    protected function minutesBetween(mixed $start, mixed $end): float
    {
        if ($start === null || $end === null) {
            return 0.0;
        }

        return round(Carbon::parse($start)->diffInMinutes(Carbon::parse($end)), 2);
    }
}

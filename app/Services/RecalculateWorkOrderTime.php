<?php

namespace App\Services;

use App\Models\WorkOrder;
use App\Models\WorkOrdersRecordTime;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Propone due strategie di ricostruzione dei tempi di una lavorazione.
 *
 * - methodA(): conservativo. Considera i segmenti con `start_at` vuoto come artefatti
 *   (durata 0), mantiene gli `end_at` già validi e chiude i segmenti aperti al
 *   `created_at` del segmento successivo (o all'`end_at` della lavorazione per l'ultimo).
 *
 * - methodB(): strategia indicata dall'utente. `end_at` di ogni segmento =
 *   `created_at` del segmento con id successivo; per l'ultimo segmento = `updated_at`.
 *
 * Nessuno dei due metodi scrive sul database: restituiscono una proposta che può
 * essere applicata con apply().
 */
class RecalculateWorkOrderTime
{
    /**
     * @return array{segments: array<int, array{id: int, start_at: Carbon, end_at: Carbon, total_minutes: float}>, total: float}
     */
    public function methodA(WorkOrder $workOrder): array
    {
        $segments = $workOrder->recordsTime()->orderBy('id')->get()->values();

        $rows = [];
        $total = 0.0;
        $previousEnd = null;

        foreach ($segments as $index => $segment) {
            $start = $this->parse($segment->start_at);

            if ($start === null) {
                // Segmento artefatto senza inizio: lo ancoriamo al punto precedente con durata 0.
                $start = $previousEnd?->copy() ?? Carbon::parse($segment->created_at);
                $end = $start->copy();
            } elseif ($segment->end_at !== null) {
                // Segmento già chiuso: manteniamo il valore esistente ma eliminiamo
                // l'eventuale sovrapposizione con l'inizio del segmento successivo.
                $end = Carbon::parse($segment->end_at);

                $nextStart = isset($segments[$index + 1])
                    ? $this->parse($segments[$index + 1]->start_at)
                    : null;

                if ($nextStart !== null && $nextStart->lessThan($end)) {
                    $end = $nextStart;
                }
            } else {
                $end = $this->openSegmentEndA($workOrder, $segments, $index);
            }

            if ($end->lessThan($start)) {
                $end = $start->copy();
            }

            $minutes = round($start->diffInMinutes($end), 2);
            $rows[] = [
                'id' => $segment->id,
                'start_at' => $start,
                'end_at' => $end,
                'total_minutes' => $minutes,
            ];
            $previousEnd = $end;
            $total += $minutes;
        }

        return ['segments' => $rows, 'total' => round($total, 2)];
    }

    /**
     * @return array{segments: array<int, array{id: int, start_at: Carbon, end_at: Carbon, total_minutes: float}>, total: float}
     */
    public function methodB(WorkOrder $workOrder): array
    {
        $segments = $workOrder->recordsTime()->orderBy('id')->get()->values();

        $rows = [];
        $total = 0.0;
        $previousEnd = null;

        foreach ($segments as $index => $segment) {
            $start = $this->parse($segment->start_at)
                ?? $previousEnd?->copy()
                ?? Carbon::parse($segment->created_at);

            if (isset($segments[$index + 1])) {
                $end = Carbon::parse($segments[$index + 1]->created_at);
            } else {
                $end = Carbon::parse($segment->updated_at);
            }

            if ($end->lessThan($start)) {
                $end = $start->copy();
            }

            $minutes = round($start->diffInMinutes($end), 2);
            $rows[] = [
                'id' => $segment->id,
                'start_at' => $start,
                'end_at' => $end,
                'total_minutes' => $minutes,
            ];
            $previousEnd = $end;
            $total += $minutes;
        }

        return ['segments' => $rows, 'total' => round($total, 2)];
    }

    /**
     * Applica una proposta generata da methodA()/methodB().
     *
     * @param  array{segments: array<int, array{id: int, start_at: Carbon, end_at: Carbon, total_minutes: float}>, total: float}  $proposal
     */
    public function apply(WorkOrder $workOrder, array $proposal): void
    {
        DB::transaction(function () use ($workOrder, $proposal): void {
            foreach ($proposal['segments'] as $row) {
                // Query builder: non tocca updated_at (che resta un riferimento storico).
                WorkOrdersRecordTime::whereKey($row['id'])->update([
                    'start_at' => $row['start_at'],
                    'end_at' => $row['end_at'],
                    'total_minutes' => $row['total_minutes'],
                ]);
            }

            $workOrder->forceFill(['total_minutes' => $proposal['total']])->save();
        });
    }

    protected function openSegmentEndA(WorkOrder $workOrder, \Illuminate\Support\Collection $segments, int $index): Carbon
    {
        if (isset($segments[$index + 1])) {
            $next = $segments[$index + 1];
            $nextCreated = Carbon::parse($next->created_at);
            $nextStart = $this->parse($next->start_at);

            // Non superiamo mai l'inizio del segmento successivo.
            if ($nextStart !== null && $nextStart->lessThan($nextCreated)) {
                return $nextStart;
            }

            return $nextCreated;
        }

        return Carbon::parse($workOrder->end_at ?? $segments[$index]->updated_at);
    }

    protected function parse(mixed $value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value);
    }
}

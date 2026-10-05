<?php

namespace App\Console\Commands;

use App\Models\WorkOrder;
use App\Models\WorkOrdersRecordTime;
use App\Services\WorkOrderTimerService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RecalculateWorkOrderTimes extends Command
{
    protected $signature = 'workorders:recalc-times
        {--work-order=* : ID lavorazione da ricalcolare (ripetibile o separati da virgola)}
        {--all : considera tutte le lavorazioni concluse con segmenti}
        {--last-end=work_order : Fine dell\'ultimo segmento: work_order|updated_at}
        {--dry-run : Mostra le modifiche senza salvarle}';

    protected $description = 'Ricalcola i tempi dei segmenti usando come fine il created_at del segmento successivo (ultimo: end della lavorazione)';

    public function handle(WorkOrderTimerService $timer): int
    {
        $hasScope = $this->option('all')
            || collect($this->option('work-order'))->contains(fn ($value) => trim((string) $value) !== '');

        if (! $hasScope) {
            return $this->listAnomalies();
        }

        $lastEnd = (string) $this->option('last-end');

        if (! in_array($lastEnd, ['updated_at', 'work_order'], true)) {
            $this->error("Opzione --last-end non valida: usare 'updated_at' o 'work_order'.");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $useWorkOrderEndForLast = $lastEnd === 'work_order';

        $this->info($dryRun
            ? 'DRY RUN — nessuna modifica verrà salvata'
            : 'Applico il ricalcolo dei tempi...');

        $workOrders = $this->resolveWorkOrders();

        if ($workOrders->isEmpty()) {
            $this->warn('Nessuna lavorazione da elaborare.');

            return self::SUCCESS;
        }

        $rows = [];

        foreach ($workOrders as $workOrder) {
            $segments = $workOrder->recordsTime()->orderBy('id')->get()->values();

            $newTotal = $this->recalculate($workOrder, $segments, $dryRun, $useWorkOrderEndForLast);

            $rows[] = [
                $workOrder->id,
                $workOrder->total_minutes,
                $newTotal,
                $segments->count(),
                $this->describeAnomalies($segments),
            ];
        }

        $this->newLine();
        $this->table(
            ['Work order', 'Total prima', 'Total dopo', 'Segmenti', 'Anomalie'],
            $rows
        );

        $this->newLine();
        $this->info(sprintf(
            '%sLavorazioni elaborate: %d',
            $dryRun ? '[DRY RUN] ' : '',
            count($rows)
        ));

        return self::SUCCESS;
    }

    /**
     * Mostra le lavorazioni concluse con disallineamenti (nessuna modifica).
     */
    protected function listAnomalies(): int
    {
        $this->warn('Nessuno scope specificato. Usa --work-order=ID (consigliato) oppure --all.');
        $this->newLine();

        $rows = WorkOrder::whereHas('recordsTime')
            ->whereNotNull('end_at')
            ->get()
            ->map(function (WorkOrder $workOrder): array {
                $segments = $workOrder->recordsTime()->orderBy('id')->get()->values();

                return [$workOrder->id, $workOrder->total_minutes, $this->describeAnomalies($segments)];
            })
            ->filter(fn (array $row) => $row[2] !== '—')
            ->values()
            ->all();

        if ($rows === []) {
            $this->info('Nessuna lavorazione con disallineamenti.');

            return self::SUCCESS;
        }

        $this->table(['Work order', 'Total attuale', 'Anomalie'], $rows);
        $this->newLine();
        $this->info(sprintf('Lavorazioni concluse con disallineamenti: %d', count($rows)));

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, WorkOrder>
     */
    protected function resolveWorkOrders(): Collection
    {
        $ids = collect($this->option('work-order'))
            ->flatMap(fn ($value) => explode(',', (string) $value))
            ->map(fn ($value) => (int) trim($value))
            ->filter()
            ->unique()
            ->values();

        if ($ids->isNotEmpty()) {
            $selected = WorkOrder::whereIn('id', $ids)->get();

            $running = $selected->filter(fn (WorkOrder $workOrder) => $workOrder->end_at === null);

            if ($running->isNotEmpty()) {
                $this->warn(sprintf(
                    'Lavorazioni in corso saltate (non concluse): %s',
                    $running->pluck('id')->implode(', ')
                ));
            }

            return $selected
                ->filter(fn (WorkOrder $workOrder) => $workOrder->end_at !== null)
                ->values();
        }

        return WorkOrder::whereHas('recordsTime')->whereNotNull('end_at')->get();
    }

    /**
     * Applica la regola: end = created_at del segmento successivo; per l'ultimo, end della
     * lavorazione (oppure updated_at con --last-end=updated_at).
     *
     * @param  Collection<int, WorkOrdersRecordTime>  $segments
     */
    protected function recalculate(
        WorkOrder $workOrder,
        Collection $segments,
        bool $dryRun,
        bool $useWorkOrderEndForLast,
    ): float {
        $previousEnd = null;
        $newTotal = 0.0;

        foreach ($segments as $index => $segment) {
            $start = $segment->start_at
                ? Carbon::parse($segment->start_at)
                : ($previousEnd
                    ?? ($workOrder->start_at
                        ? Carbon::parse($workOrder->start_at)
                        : Carbon::parse($segment->created_at)));

            if (isset($segments[$index + 1])) {
                $end = Carbon::parse($segments[$index + 1]->created_at);
            } elseif ($useWorkOrderEndForLast && $workOrder->end_at) {
                $end = Carbon::parse($workOrder->end_at);
            } else {
                $end = Carbon::parse($segment->updated_at);
            }

            if ($end->lessThan($start)) {
                $end = $start->copy();
            }

            $minutes = round($start->diffInMinutes($end), 2);
            $newTotal += $minutes;
            $previousEnd = $end;

            if (! $dryRun) {
                $segment->forceFill([
                    'start_at' => $start,
                    'end_at' => $end,
                    'total_minutes' => $minutes,
                ])->save();
            }
        }

        $newTotal = round($newTotal, 2);

        if (! $dryRun) {
            $workOrder->forceFill(['total_minutes' => $newTotal])->save();
        }

        return $newTotal;
    }

    /**
     * @param  Collection<int, WorkOrdersRecordTime>  $segments
     */
    protected function describeAnomalies(Collection $segments): string
    {
        $anomalies = [];
        $previous = null;

        foreach ($segments as $segment) {
            if ($segment->end_at === null) {
                $anomalies[] = "id={$segment->id}:aperto";
            } elseif ($segment->start_at && Carbon::parse($segment->end_at)->lessThan(Carbon::parse($segment->start_at))) {
                $anomalies[] = "id={$segment->id}:negativo";
            } elseif ($previous && Carbon::parse($segment->start_at)->lessThan(Carbon::parse($previous->end_at))) {
                $anomalies[] = "id={$segment->id}:sovrapposto";
            }

            $previous = $segment;
        }

        return $anomalies === [] ? '—' : implode(', ', $anomalies);
    }
}

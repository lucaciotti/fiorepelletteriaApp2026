<?php

namespace App\Console\Commands;

use App\Models\WorkOrder;
use App\Services\RecalculateWorkOrderTime;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class RecalculateWorkOrderTimes extends Command
{
    protected $signature = 'workorders:recalc-times
        {--work-order=* : ID lavorazione da ricalcolare (ripetibile o separati da virgola)}
        {--all : verifica tutte le lavorazioni concluse}
        {--method= : Applica A o B a tutte senza chiedere (A|B)}
        {--dry-run : Mostra le proposte senza salvarle}';

    protected $description = 'Per ogni lavorazione disallineata propone il ricalcolo dei tempi con il Metodo A o il Metodo B';

    public function handle(RecalculateWorkOrderTime $recalculator): int
    {
        $hasScope = $this->option('all')
            || collect($this->option('work-order'))->contains(fn ($value) => trim((string) $value) !== '');

        if (! $hasScope) {
            return $this->listAnomalies();
        }

        $method = strtoupper((string) $this->option('method'));

        if ($method !== '' && ! in_array($method, ['A', 'B'], true)) {
            $this->error('Opzione --method non valida: usare A oppure B.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');

        if ($method !== '') {
            $this->info("Applico il Metodo {$method}".($dryRun ? ' (DRY RUN)' : '').'.');
        } elseif ($dryRun) {
            $this->info('DRY RUN — mostro le proposte, nessuna modifica.');
        } else {
            $this->info('Per ogni lavorazione disallineata potrai scegliere il Metodo A, il Metodo B o saltare.');
        }

        $workOrders = $this->resolveWorkOrders();

        if ($workOrders->isEmpty()) {
            $this->warn('Nessuna lavorazione da elaborare.');

            return self::SUCCESS;
        }

        $applied = ['A' => 0, 'B' => 0, 'skip' => 0];

        foreach ($workOrders as $workOrder) {
            $segments = $workOrder->recordsTime()->orderBy('id')->get()->values();

            if ($segments->isEmpty()) {
                continue;
            }

            $anomalies = $this->describeAnomalies($segments);

            if ($anomalies === '—') {
                $this->line("Work order {$workOrder->id}: nessun disallineamento, saltata.");
                $applied['skip']++;

                continue;
            }

            $proposalA = $recalculator->methodA($workOrder);
            $proposalB = $recalculator->methodB($workOrder);

            $this->renderProposal($workOrder, $segments, $proposalA, $proposalB, $anomalies);

            if ($dryRun && $method === '') {
                continue;
            }

            $choice = $method !== '' ? $method : (string) $this->choice(
                "Work order {$workOrder->id}: quale metodo applico?",
                ['A' => 'Metodo A (conservativo)', 'B' => 'Metodo B (created_at/updated_at)', 'skip' => 'Salta'],
                'skip'
            );

            if ($choice === 'A' || $choice === 'B') {
                if ($dryRun) {
                    $this->line("  [DRY RUN] Applicherei il Metodo {$choice}.");
                } else {
                    $recalculator->apply($workOrder, $choice === 'A' ? $proposalA : $proposalB);
                    $this->info("  Applicato Metodo {$choice} a work order {$workOrder->id}.");
                }
            } else {
                $applied['skip']++;
                $this->line("  Work order {$workOrder->id} saltata.");
            }

            $this->newLine();
        }

        $this->info(sprintf(
            'Metodo A: %d — Metodo B: %d — Saltate: %d',
            $applied['A'],
            $applied['B'],
            $applied['skip']
        ));

        return self::SUCCESS;
    }

    /**
     * Mostra le lavorazioni concluse con disallineamenti (nessuna modifica).
     */
    protected function listAnomalies(): int
    {
        $this->warn('Nessuno scope specificato. Usa --work-order=ID oppure --all.');
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
     * @param  Collection<int, \App\Models\WorkOrdersRecordTime>  $segments
     * @param  array{segments: array<int, array<string, mixed>>, total: float}  $proposalA
     * @param  array{segments: array<int, array<string, mixed>>, total: float}  $proposalB
     */
    protected function renderProposal(
        WorkOrder $workOrder,
        Collection $segments,
        array $proposalA,
        array $proposalB,
        string $anomalies,
    ): void {
        $aById = collect($proposalA['segments'])->keyBy('id');
        $bById = collect($proposalB['segments'])->keyBy('id');

        $rows = [];

        foreach ($segments as $segment) {
            $a = $aById->get($segment->id);
            $b = $bById->get($segment->id);

            $rows[] = [
                $segment->id,
                $this->format($segment->start_at),
                $this->format($segment->end_at),
                $segment->total_minutes ?? '—',
                $this->format($a['end_at'] ?? null),
                $a['total_minutes'] ?? '',
                $this->format($b['end_at'] ?? null),
                $b['total_minutes'] ?? '',
            ];
        }

        $this->newLine();
        $this->line("Work order {$workOrder->id} — anomalie: {$anomalies}");
        $this->table(
            ['Seg', 'Start', 'End att.', 'Min att.', 'End A', 'Min A', 'End B', 'Min B'],
            $rows
        );
        $this->line(sprintf(
            'Totale attuale: %s  |  Metodo A: %s  |  Metodo B: %s',
            $workOrder->total_minutes ?? '—',
            $proposalA['total'],
            $proposalB['total']
        ));
    }

    /**
     * @param  Collection<int, \App\Models\WorkOrdersRecordTime>  $segments
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

    protected function format(mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        return Carbon::parse($value)->format('d/m H:i');
    }
}

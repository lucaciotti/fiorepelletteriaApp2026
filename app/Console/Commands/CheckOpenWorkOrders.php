<?php

namespace App\Console\Commands;

use App\Models\WorkOrder;
use App\Notifications\WorkOrderNotifier;
use App\Settings\WorkOrderSettings;
use Illuminate\Console\Command;

class CheckOpenWorkOrders extends Command
{
    protected $signature = 'workorders:check-open';

    protected $description = 'Notifica le lavorazioni non in pausa e non terminate (controllo schedulato)';

    public function handle(WorkOrderSettings $settings): int
    {
        if (! $settings->enabled) {
            $this->info('Controllo disattivato dalle impostazioni.');

            return self::SUCCESS;
        }

        $openWorkOrders = WorkOrder::query()
            ->whereNull('end_at')
            ->where(function ($query): void {
                $query->where('paused', false)->orWhereNull('paused');
            })
            ->with(['operator', 'processType', 'order'])
            ->get();

        if ($openWorkOrders->isEmpty()) {
            $this->info('Nessuna lavorazione aperta e non in pausa.');

            return self::SUCCESS;
        }

        foreach ($openWorkOrders as $workOrder) {
            WorkOrderNotifier::notifyAdmins(
                title: 'Lavorazione non chiusa',
                body: $workOrder->fulldescr.' — operatore: '.($workOrder->operator?->name ?? '—'),
                actionUrl: url('/work-orders/'.$workOrder->id.'/edit'),
            );
        }

        $this->info(sprintf('Notificate %d lavorazioni aperte alle %s.', $openWorkOrders->count(), $settings->alert_hour));

        return self::SUCCESS;
    }
}

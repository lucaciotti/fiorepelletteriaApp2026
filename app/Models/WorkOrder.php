<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;

class WorkOrder extends Model
{
    protected $guarded = [
        'id'
    ];
    protected $appends = ['status', 'fulldescr'];

    public function getStatusAttribute()
    {
        if ($this->paused) {
                return 'paused';
        }
        if ($this->end_at == null) {
                return 'started';
        }
        return 'ended';
    }

    public function getFulldescrAttribute()
    {
        return '[' . $this->processType->name . '] del ' . (new Carbon($this->start_at))->format('d/m/Y') . ' di: ' . $this->operator->name;
    }

    public function getOrdrifAttribute()
    {
        return 'Ord. n.' . $this->order->number . ' del ' . (new Carbon($this->order->date))->format('d/m/Y');
    }

    public function processType(): BelongsTo
    {
        return $this->belongsTo(ProcessType::class);
    }

    public function operator(): BelongsTo
    {
        return $this->belongsTo(Operator::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderRow(): BelongsTo
    {
        return $this->belongsTo(OrderRow::class);
    }

    public function customer(): HasOneThrough
    {
        return $this->hasOneThrough(Customer::class, Order::class, 'id', 'id', 'order_id', 'customer_id');
    }

    public function product(): HasOneThrough
    {
        return $this->hasOneThrough(Product::class, OrderRow::class, 'id', 'id', 'order_row_id', 'product_id');
    }

    public function recordsTime(): HasMany
    {
        return $this->hasMany(WorkOrdersRecordTime::class, 'work_order_id', 'id');
    }
}

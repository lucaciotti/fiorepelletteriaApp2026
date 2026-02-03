<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $guarded = [
        'id'
    ];
        
    protected $appends = ['fulldescr'];
    // protected $casts= [
    //         'date' => 'datetime:d/m/Y',
    //         // 'created_at' => 'datetime:Y-m-d',
    //         // 'created_at' => 'datetime:Y-m-d',
    //     ];

    public function getFulldescrAttribute()
    {
        return 'Ord. n.'.$this->number . ' del ' . (new Carbon($this->date))->format('d/m/Y') . ' - Cliente: ' . $this->customer->name;
    }

    public function getCountProductAttribute()
    {
        return count($this->rows->where('closed', false));
    }


    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function rows(): HasMany
    {
        return $this->hasMany(OrderRow::class);
    }
}

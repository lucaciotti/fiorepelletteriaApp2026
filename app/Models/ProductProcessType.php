<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductProcessType extends Model
{
    protected $guarded = [
        'id'
    ];

    public function processType(): BelongsTo
    {
        return $this->belongsTo(ProcessType::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

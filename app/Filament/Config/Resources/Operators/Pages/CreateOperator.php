<?php

namespace App\Filament\Config\Resources\Operators\Pages;

use App\Filament\Config\Resources\Operators\OperatorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOperator extends CreateRecord
{
    protected static string $resource = OperatorResource::class;
}

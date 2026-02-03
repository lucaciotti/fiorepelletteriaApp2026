<?php

namespace App\Filament\Admin\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Auth;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;
use UnitEnum;

class OrderStat extends Page
{

    use HasPageShield;

    protected string $view = 'filament.pages.order-stat';

    protected static string | UnitEnum | null $navigationGroup = 'Statistiche';
    protected static ?int $navigationSort = 2;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;
   
    public static ?string $title = 'Statistiche Lavorazioni';

    // public static function canAccess(): bool
    // {
    //     return Auth::user()->hasRole('admin') || Auth::user()->hasRole('super_admin');
    // }

}

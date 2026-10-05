<?php

namespace App\Statistics;

use Illuminate\Support\Facades\Session;

/**
 * Incapsula lo stato della pagina "Statistiche Lavorazioni".
 *
 * Nota: usa la Session, quindi è condiviso tra le schede dello stesso browser
 * (rischio multi-tab). Centralizzare qui evita accessi diretti sparsi.
 */
class OrderStatState
{
    public const DEFAULT_GROUP_TYPE = 'customer_id-order_id-product_id-process_type_id';

    private const LEGACY_GROUP_TYPE = 'customer_id-number-product_id-process_type_id';

    private const GROUP_TYPE = 'orderstat.form.groupType';

    private const FILTERS = [
        'products' => 'orderstat.form.filter.products',
        'customers' => 'orderstat.form.filter.customers',
        'operators' => 'orderstat.form.filter.operators',
    ];

    public static function groupType(): string
    {
        $groupType = Session::get(self::GROUP_TYPE);

        if (! $groupType || $groupType === self::LEGACY_GROUP_TYPE) {
            $groupType = self::DEFAULT_GROUP_TYPE;
            Session::put(self::GROUP_TYPE, $groupType);
        }

        return $groupType;
    }

    public static function setGroupType(string $groupType): void
    {
        Session::put(self::GROUP_TYPE, $groupType);
    }

    /**
     * @return array{products: array<int, mixed>, customers: array<int, mixed>, operators: array<int, mixed>}
     */
    public static function filters(): array
    {
        return [
            'products' => Session::get(self::FILTERS['products']) ?? [],
            'customers' => Session::get(self::FILTERS['customers']) ?? [],
            'operators' => Session::get(self::FILTERS['operators']) ?? [],
        ];
    }

    /**
     * @param  array<int, mixed>  $values
     */
    public static function setFilter(string $name, array $values): void
    {
        if (isset(self::FILTERS[$name])) {
            Session::put(self::FILTERS[$name], $values);
        }
    }
}

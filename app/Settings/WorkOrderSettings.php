<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class WorkOrderSettings extends Settings
{
    /** Attiva/disattiva il controllo schedulato delle lavorazioni aperte. */
    public bool $enabled;

    /** Ora (HH:MM) del controllo giornaliero, impostata dall'admin. */
    public string $alert_hour;

    public static function group(): string
    {
        return 'workorders';
    }
}

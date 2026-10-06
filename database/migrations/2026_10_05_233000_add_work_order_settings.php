<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('workorders.enabled', true);
        $this->migrator->add('workorders.alert_hour', '18:00');
    }
};

<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

return new class extends SettingsMigration
{
    public function up(): void
    {
        $this->migrator->add('sites.site_name', 'FioreApp');
        $this->migrator->add('sites.site_description', 'Fiore Pelletteria app');
        $this->migrator->add('sites.site_keywords', 'Programming');
        $this->migrator->add('sites.site_profile', '');
        $this->migrator->add('sites.site_logo', '');
        $this->migrator->add('sites.site_author', 'Luca Ciotti');
        $this->migrator->add('sites.site_email', 'luca.ciotti@gmail.com');
        $this->migrator->add('sites.site_phone', '');
        $this->migrator->add('sites.site_social', []);
    }
};

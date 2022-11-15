<?php

namespace Spatie\LaravelSettings\Migrations;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;
use Spatie\LaravelSettings\Settings;

abstract class SettingsMigration extends Migration
{
    private Settings $settings;

    protected SettingsMigrator $migrator;

    public function up()
    {
        if (! $this->determineSettingsClassFromMigrationClass()) {
            return;
        }

        $this->migrator->inGroup($this->settings->group(), function (SettingsBlueprint $blueprint) {
            foreach ($this->settings->columns() as $column => $args) {
                $blueprint->addIfNotExists($column, ...$args);
            }
        });
    }

    public function __construct()
    {
        $this->migrator = app(SettingsMigrator::class);
    }

    public function down()
    {
        if (! $this->determineSettingsClassFromMigrationClass()) {
            return;
        }

        $this->migrator->inGroup($this->settings->group(), function (SettingsBlueprint $blueprint) {
            foreach ($this->settings->columns() as $column => $args) {
                $blueprint->deleteIfExists($column);
            }
        });
    }

    private function determineSettingsClassFromMigrationClass(): bool
    {
        if (! Str::startsWith(static::class, 'Create')) {
            return false;
        }

        try {
            $this->settings = app(Str::replaceFirst('Create', '\\App\\Settings\\', static::class));
        } catch (\Throwable $th) {
            return false;
        }

        return true;
    }
}

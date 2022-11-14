<?php

namespace Spatie\LaravelSettings\Console;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use InvalidArgumentException;

class MakeSettingsMigrationCommand extends Command
{
    protected $signature = 'make:settings-migration {name : The name of the migration} {path? : Path to write migration file to}';

    protected $description = 'Create a new settings migration file';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();

        $this->files = $files;
    }

    public function handle(): void
    {
        $name = trim($this->input->getArgument('name'));
        $path = trim($this->input->getArgument('path'));

        // If path is still empty we get the first path from new settings.migrations_paths config
        if (empty($path)) {
            $path = $this->resolveMigrationPaths()[0];
        }

        $this->ensureMigrationDoesntAlreadyExist($name, $path);

        $this->files->ensureDirectoryExists($path);

        $this->files->put(
            $this->getPath($name, $path),
            str_replace('{{ class }}', $name, $this->getMigrationStub())
        );

        $this->createSettings(Str::after($name, 'Create'));
    }

    protected function getMigrationStub(): string
    {
        return <<<EOT
<?php

use Spatie\LaravelSettings\Migrations\SettingsMigration;

class {{ class }} extends SettingsMigration
{
    public function up(): void
    {

    }
}

EOT;
    }

    protected function createSettings($name)
    {
        $group = Str::of($name)->beforeLast('Settings')->kebab();

        $this->files->ensureDirectoryExists(app_path('Settings'));

        $this->files->put(
            app_path('Settings/'.$name.'.php'),
            str_replace(['{{ class }}', '{{ group }}'], [$name, $group], $this->getSettingsStub())
        );
    }

    protected function getSettingsStub(): string
    {
        return <<<EOT
<?php

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

class {{ class }} extends Settings
{
    public static function group(): string
    {
        return '{{ group }}';
    }
}

EOT;
    }

    protected function ensureMigrationDoesntAlreadyExist($name, $migrationPath = null): void
    {
        if (! empty($migrationPath)) {
            $migrationFiles = $this->files->glob($migrationPath . '/*.php');

            foreach ($migrationFiles as $migrationFile) {
                $this->files->requireOnce($migrationFile);
            }
        }

        if (class_exists($className = Str::studly($name))) {
            throw new InvalidArgumentException("A {$className} class already exists.");
        }
    }

    protected function getPath($name, $path): string
    {
        return $path . '/' . date('Y_m_d_His') . '_' . Str::snake($name) . '.php';
    }

    protected function resolveMigrationPaths(): array
    {
        return ! empty(config('settings.migrations_path'))
            ? [config('settings.migrations_path')]
            : config('settings.migrations_paths');
    }
}

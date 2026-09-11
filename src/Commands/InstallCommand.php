<?php

namespace Breadthe\StarterTimezone\Commands;

use Breadthe\StarterTimezone\Scaffolding\StarterKitScaffolder;
use Illuminate\Console\Command;

use function Laravel\Prompts\multiselect;

class InstallCommand extends Command
{
    /**
     * @var array<string, string>
     */
    private const OPTIONS = [
        'migration' => 'Add a timezone column to the users table',
        'registration' => 'Add a timezone dropdown to the registration page',
        'profile' => 'Add a timezone dropdown to /settings/profile',
    ];

    protected $signature = 'starter-timezone:install
        {--migration : Add only the users timezone migration}
        {--registration : Add only the registration timezone field}
        {--profile : Add only the profile-settings timezone field}
        {--path= : Scaffold an application at the given base path}
        {--force : Apply changes even when an existing timezone field is detected}';

    protected $description = 'Add timezone support to the Laravel Livewire starter kit';

    public function handle(StarterKitScaffolder $scaffolder): int
    {
        $options = $this->installOptions();

        if ($options === []) {
            $this->components->warn('No timezone scaffolding was selected.');

            return self::SUCCESS;
        }

        $result = $scaffolder->scaffold(
            basePath: $this->option('path') ?: $this->laravel->basePath(),
            addMigration: in_array('migration', $options, true),
            addRegistration: in_array('registration', $options, true),
            addProfile: in_array('profile', $options, true),
            force: (bool) $this->option('force'),
        );

        foreach ($result->changes as $change) {
            $this->components->info($change);
        }

        foreach ($result->warnings as $warning) {
            $this->components->warn($warning);
        }

        if ($result->failed()) {
            foreach ($result->errors as $error) {
                $this->components->error($error);
            }

            return self::FAILURE;
        }

        if (in_array('migration', $options, true)) {
            $this->newLine();
            $this->line('Run <comment>php artisan migrate</comment> when you are ready to apply the users table migration.');
        }

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function selectedOptions(): array
    {
        return array_keys(array_filter([
            'migration' => $this->option('migration'),
            'registration' => $this->option('registration'),
            'profile' => $this->option('profile'),
        ]));
    }

    /**
     * @return array<int, string>
     */
    private function installOptions(): array
    {
        $selected = $this->selectedOptions();

        if ($selected !== []) {
            return $selected;
        }

        if (! $this->input->isInteractive()) {
            return array_keys(self::OPTIONS);
        }

        return multiselect(
            label: 'Which timezone features would you like to install?',
            options: self::OPTIONS,
            default: array_keys(self::OPTIONS),
            hint: 'Use space to select, enter to confirm.',
        );
    }
}

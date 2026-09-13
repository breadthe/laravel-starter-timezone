<?php

use Breadthe\StarterTimezone\Scaffolding\ScaffoldResult;
use Breadthe\StarterTimezone\Scaffolding\StarterKitScaffolder;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Filesystem\Filesystem;

test('the interactive installer presents a multiselect with every option selected by default', function () {
    $basePath = sys_get_temp_dir().'/starter-timezone-'.uniqid();
    $scaffolder = Mockery::mock(StarterKitScaffolder::class);

    $scaffolder->shouldReceive('scaffold')
        ->once()
        ->with($basePath, true, true, true, false)
        ->andReturn(new ScaffoldResult);

    app()->instance(StarterKitScaffolder::class, $scaffolder);

    $this->artisan('starter-timezone:install', [
        '--path' => $basePath,
    ])
        ->expectsChoice(
            'Which timezone features would you like to install?',
            ['migration', 'registration', 'profile'],
            [
                'migration' => 'Add a timezone column to the users table',
                'registration' => 'Add a timezone dropdown to the registration page',
                'profile' => 'Add a timezone dropdown to /settings/profile',
            ],
        )
        ->expectsChoice(
            'Would you like to run the migration automatically after installation?',
            ['migration'],
            ['migration' => 'Run the users table migration'],
        )
        ->expectsOutputToContain('Run php artisan migrate')
        ->assertSuccessful();
});

test('the installer runs only the timezone migration automatically when selected', function () {
    $files = app(Filesystem::class);
    $migrationPath = app()->basePath().'/database/migrations/00000000000000_add_timezone_to_users_table.php';
    $files->ensureDirectoryExists(dirname($migrationPath));
    $files->put($migrationPath, '<?php');

    $scaffolder = Mockery::mock(StarterKitScaffolder::class);
    $scaffolder->shouldReceive('scaffold')
        ->once()
        ->with(app()->basePath(), true, false, false, false)
        ->andReturn(new ScaffoldResult);

    app()->instance(StarterKitScaffolder::class, $scaffolder);

    $migrate = new class extends Command
    {
        public bool $wasForced = false;

        /** @var array<int, string> */
        public array $migrationPaths = [];

        protected $signature = 'migrate {--force} {--path=*} {--realpath}';

        public function handle(): int
        {
            $this->wasForced = (bool) $this->option('force');
            $this->migrationPaths = (array) $this->option('path');

            return self::SUCCESS;
        }
    };

    app(Kernel::class)->registerCommand($migrate);

    $this->artisan('starter-timezone:install', [
        '--migration' => true,
        '--no-interaction' => true,
    ])->assertSuccessful();

    expect($migrate->wasForced)->toBeTrue()
        ->and($migrate->migrationPaths)->toContain($migrationPath);

    $files->delete($migrationPath);
});

test('the installer leaves the migration for manual execution when cleared', function () {
    $basePath = sys_get_temp_dir().'/starter-timezone-'.uniqid();
    $scaffolder = Mockery::mock(StarterKitScaffolder::class);

    $scaffolder->shouldReceive('scaffold')
        ->once()
        ->with($basePath, true, false, false, false)
        ->andReturn(new ScaffoldResult);

    app()->instance(StarterKitScaffolder::class, $scaffolder);

    $this->artisan('starter-timezone:install', [
        '--migration' => true,
        '--path' => $basePath,
    ])
        ->expectsChoice(
            'Would you like to run the migration automatically after installation?',
            [],
            ['migration' => 'Run the users table migration'],
        )
        ->expectsOutputToContain('Run php artisan migrate')
        ->assertSuccessful();
});

test('the installer scaffolds all timezone surfaces non-interactively', function () {
    $basePath = sys_get_temp_dir().'/starter-timezone-'.uniqid();
    $files = app(Filesystem::class);

    $files->ensureDirectoryExists($basePath.'/app/Models');
    $files->ensureDirectoryExists($basePath.'/app/Actions/Fortify');
    $files->ensureDirectoryExists($basePath.'/resources/views/pages/auth');
    $files->ensureDirectoryExists($basePath.'/resources/views/pages/settings');
    $files->ensureDirectoryExists($basePath.'/database/migrations');
    $files->put($basePath.'/app/Models/User.php', "<?php\nprotected \$fillable = [\n    'name',\n    'email',\n];\n");
    $files->put($basePath.'/app/Actions/Fortify/CreateNewUser.php', "<?php\nValidator::make(\$input, [\n    'email' => ['required'],\n])->validate();\nreturn User::create([\n    'email' => \$input['email'],\n]);\n");
    $files->put($basePath.'/resources/views/pages/auth/register.blade.php', "<!-- Password -->\n<flux:input name=\"password\" />\n");
    $files->put($basePath.'/resources/views/pages/settings/⚡profile.blade.php', <<<'BLADE'
<?php
new class {
    public string $email = '';
    public function mount(): void { $this->email = Auth::user()->email; }
    public function updateProfileInformation(): void { $this->validate([
        'email' => ['required'],
    ]); }
};
?>
<div class="flex items-center gap-4"></div>
BLADE);

    $this->artisan('starter-timezone:install', [
        '--no-interaction' => true,
        '--path' => $basePath,
    ])
        ->expectsOutputToContain('Run php artisan migrate')
        ->assertSuccessful();

    expect($files->get($basePath.'/app/Models/User.php'))->toContain("'timezone',")
        ->and($files->get($basePath.'/app/Actions/Fortify/CreateNewUser.php'))->toContain("'timezone' => \$input['timezone'],")
        ->and($files->get($basePath.'/resources/views/pages/auth/register.blade.php'))->toContain('name="timezone"')
        ->and($files->get($basePath.'/resources/views/pages/settings/⚡profile.blade.php'))->toContain('wire:model="timezone"')
        ->and(glob($basePath.'/database/migrations/*_add_timezone_to_users_table.php'))->not->toBeEmpty();

    $this->artisan('starter-timezone:install', [
        '--migration' => true,
        '--path' => $basePath,
        '--force' => true,
        '--no-interaction' => true,
    ])->assertSuccessful();

    expect(glob($basePath.'/database/migrations/*_add_timezone_to_users_table.php'))->toHaveCount(1);

    $files->deleteDirectory($basePath);
});

test('the installer supports the non-emoji profile path from the official starter kit', function () {
    $basePath = sys_get_temp_dir().'/starter-timezone-'.uniqid();
    $files = app(Filesystem::class);

    $files->ensureDirectoryExists($basePath.'/app/Models');
    $files->ensureDirectoryExists($basePath.'/resources/views/pages/settings');
    $files->put($basePath.'/app/Models/User.php', "<?php\nprotected \$fillable = [\n    'email',\n];\n");
    $files->put($basePath.'/resources/views/pages/settings/profile.blade.php', <<<'BLADE'
<?php
new class {
    public string $email = '';
    public function mount(): void { $this->email = Auth::user()->email; }
    public function updateProfileInformation(): void { $this->validate([
        'email' => ['required'],
    ]); }
};
?>
<div class="flex items-center gap-4"></div>
BLADE);

    $this->artisan('starter-timezone:install', [
        '--profile' => true,
        '--path' => $basePath,
    ])->assertSuccessful();

    expect($files->get($basePath.'/resources/views/pages/settings/profile.blade.php'))
        ->toContain('wire:model="timezone"');

    $files->deleteDirectory($basePath);
});

test('the installer patches the current starter kit shared profile rules', function () {
    $basePath = sys_get_temp_dir().'/starter-timezone-'.uniqid();
    $files = app(Filesystem::class);

    $files->ensureDirectoryExists($basePath.'/app/Models');
    $files->ensureDirectoryExists($basePath.'/app/Actions/Fortify');
    $files->ensureDirectoryExists($basePath.'/app/Concerns');
    $files->ensureDirectoryExists($basePath.'/resources/views/pages/auth');
    $files->ensureDirectoryExists($basePath.'/resources/views/pages/settings');
    $files->put($basePath.'/app/Models/User.php', "<?php\n#[Fillable(['name', 'email', 'password'])]\nclass User {}\n");
    $files->put($basePath.'/app/Actions/Fortify/CreateNewUser.php', <<<'PHP'
<?php
Validator::make($input, [
    ...$this->profileRules(),
])->validate();
return User::create([
    'email' => $input['email'],
]);
PHP);
    $files->put($basePath.'/app/Concerns/ProfileValidationRules.php', <<<'PHP'
<?php
trait ProfileValidationRules
{
    protected function profileRules(?int $userId = null): array
    {
        return [
            'email' => $this->emailRules($userId),
        ];
    }
}
PHP);
    $files->put($basePath.'/resources/views/pages/auth/register.blade.php', "<!-- Password -->\n<flux:input name=\"password\" />\n");
    $files->put($basePath.'/resources/views/pages/settings/profile.blade.php', <<<'BLADE'
<?php
new class {
    public string $email = '';
    public function mount(): void { $this->email = Auth::user()->email; }
    public function updateProfileInformation(): void { $this->validate($this->profileRules(1)); }
};
?>
<div class="flex items-center gap-4"></div>
BLADE);

    $this->artisan('starter-timezone:install', [
        '--registration' => true,
        '--profile' => true,
        '--path' => $basePath,
    ])->assertSuccessful();

    expect($files->get($basePath.'/app/Concerns/ProfileValidationRules.php'))
        ->toContain("'timezone' => ['required', 'string', \\Illuminate\\Validation\\Rule::in(\\DateTimeZone::listIdentifiers())],")
        ->and($files->get($basePath.'/app/Actions/Fortify/CreateNewUser.php'))
        ->toContain("'timezone' => \$input['timezone'],")
        ->and($files->get($basePath.'/resources/views/pages/settings/profile.blade.php'))
        ->toContain('wire:model="timezone"');

    $files->deleteDirectory($basePath);
});

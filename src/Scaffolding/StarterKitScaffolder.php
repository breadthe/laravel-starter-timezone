<?php

namespace Breadthe\StarterTimezone\Scaffolding;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;
use Throwable;

class StarterKitScaffolder
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly StarterKitPatcher $patcher,
    ) {}

    public function scaffold(
        string $basePath,
        bool $addMigration,
        bool $addRegistration,
        bool $addProfile,
        bool $force = false,
    ): ScaffoldResult {
        $result = new ScaffoldResult;
        $updates = [];

        try {
            if ($addMigration) {
                $migrationPath = $this->migrationPath($basePath);

                if (glob($this->path($basePath, 'database/migrations/*_add_timezone_to_users_table.php')) !== []) {
                    $result->warnings[] = 'The timezone migration already exists.';
                } else {
                    $updates[$migrationPath] = $this->files->get($this->migrationStubPath());
                }
            }

            if ($addRegistration || $addProfile) {
                $userModelPath = $this->path($basePath, 'app/Models/User.php');
                $updates[$userModelPath] = $this->patch($userModelPath, fn (string $contents): string => $this->patcher->userModel($contents), $force);
            }

            if ($addRegistration) {
                $createUserPath = $this->path($basePath, 'app/Actions/Fortify/CreateNewUser.php');
                $registrationPath = $this->findPath($basePath, [
                    'resources/views/pages/auth/register.blade.php',
                    'resources/views/auth/register.blade.php',
                ]);

                $createUserContents = $this->files->get($createUserPath);
                $updates[$createUserPath] = $this->patch($createUserPath, fn (string $contents): string => $this->patcher->createNewUser($contents), $force);
                $updates[$registrationPath] = $this->patch($registrationPath, fn (string $contents): string => $this->patcher->registrationView($contents), $force);

                if (str_contains($createUserContents, '$this->profileRules(')) {
                    $this->addProfileValidationRules($basePath, $updates, $force);
                }
            }

            if ($addProfile) {
                $profilePath = $this->findPath($basePath, [
                    'resources/views/pages/settings/⚡profile.blade.php',
                    'resources/views/pages/settings/profile.blade.php',
                ]);

                $profileContents = $this->files->get($profilePath);
                $updates[$profilePath] = $this->patch($profilePath, fn (string $contents): string => $this->patcher->profileView($contents), $force);

                if (str_contains($profileContents, '$this->profileRules(')) {
                    $this->addProfileValidationRules($basePath, $updates, $force);
                }
            }
        } catch (Throwable $exception) {
            $result->errors[] = $exception->getMessage();

            return $result;
        }

        foreach ($updates as $path => $contents) {
            $this->files->ensureDirectoryExists(dirname($path));
            $this->files->put($path, $contents);
            $result->changes[] = 'Updated '.Str::after($path, $basePath.DIRECTORY_SEPARATOR);
        }

        return $result;
    }

    private function patch(string $path, callable $patch, bool $force): string
    {
        if (! $this->files->exists($path)) {
            throw new UnsupportedStarterKit('Expected starter-kit file is missing: '.basename($path));
        }

        $contents = $this->files->get($path);

        if (! $force && str_contains($contents, 'timezone')) {
            throw new UnsupportedStarterKit('Timezone scaffolding is already present in '.basename($path).'. Use --force only after reviewing that file.');
        }

        return $patch($contents);
    }

    /**
     * @param  array<int, string>  $candidates
     */
    private function findPath(string $basePath, array $candidates): string
    {
        foreach ($candidates as $candidate) {
            $path = $this->path($basePath, $candidate);

            if ($this->files->exists($path)) {
                return $path;
            }
        }

        throw new UnsupportedStarterKit('Could not find a supported Livewire starter-kit view.');
    }

    private function migrationPath(string $basePath): string
    {
        return $this->path($basePath, 'database/migrations/'.date('Y_m_d_His').'_add_timezone_to_users_table.php');
    }

    /**
     * @param  array<string, string>  $updates
     */
    private function addProfileValidationRules(string $basePath, array &$updates, bool $force): void
    {
        $path = $this->path($basePath, 'app/Concerns/ProfileValidationRules.php');

        if (array_key_exists($path, $updates)) {
            return;
        }

        $updates[$path] = $this->patch($path, fn (string $contents): string => $this->patcher->profileValidationRules($contents), $force);
    }

    private function migrationStubPath(): string
    {
        return dirname(__DIR__, 2).'/stubs/add_timezone_to_users_table.php.stub';
    }

    private function path(string $basePath, string $path): string
    {
        return $basePath.DIRECTORY_SEPARATOR.$path;
    }
}

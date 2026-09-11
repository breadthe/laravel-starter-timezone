# Laravel Starter Timezone

[![Tests](https://github.com/breadthe/laravel-starter-timezone/actions/workflows/tests.yml/badge.svg)](https://github.com/breadthe/laravel-starter-timezone/actions/workflows/tests.yml)

Adds a user's IANA timezone to a fresh Laravel Livewire starter-kit application.

The installer creates a reversible `users.timezone` migration and safely scaffolds timezone controls into the Fortify registration action, the registration page, and the Livewire profile settings page. The browser detects a supported IANA timezone automatically; `UTC` is the reliable fallback.

## Supported applications

This is intentionally not a general-purpose timezone package. It is for a Laravel application scaffolded with the **Livewire starter kit** and its **Laravel Fortify-based authentication**.

- PHP 8.3+
- Laravel 12 or 13 with the current Livewire 4 starter-kit structure
- Laravel Fortify registration (the starter kit's standard `App\Actions\Fortify\CreateNewUser` action)
- Livewire 4 and Flux UI, using the starter kit's full-page profile component at `resources/views/pages/settings/profile.blade.php` (with or without Livewire's `⚡` filename prefix)
- Laravel's standard database-backed user authentication

It does **not** support WorkOS, Inertia, React, Livewire 3 / Volt-era starter kits, API-only Fortify, a custom authentication action, or a heavily customized starter-kit profile page. Those integrations need a purpose-built installer rather than a fragile patcher.

Install it immediately after scaffolding, before changing the auth views or profile component. The command recognizes the current Laravel 12/13 Livewire starter-kit registration view at either `resources/views/auth/register.blade.php` or `resources/views/pages/auth/register.blade.php`.

## Installation

```bash
composer require breadthe/laravel-starter-timezone
php artisan starter-timezone:install
```

The interactive command asks three questions, each defaulting to **yes**:

1. Add a `timezone` string column to `users`, defaulting to `UTC`.
2. Add a timezone select field to registration, including browser detection.
3. Add a timezone select field and a **Detect** button to `/settings/profile`.

Finish by running the migration:

```bash
php artisan migrate
```

For unattended provisioning, no option is needed: non-interactive runs select all three operations.

```bash
php artisan starter-timezone:install --no-interaction
```

To apply only one surface, pass one or more selection flags:

```bash
php artisan starter-timezone:install --migration
php artisan starter-timezone:install --registration --profile
```

The installer stops before writing anything if it cannot recognize a required starter-kit file. It also refuses to patch a file that already mentions `timezone`, protecting hand-written changes. Review that file first; only then use `--force` if you want the installer to continue with the remaining supported changes.

## What is generated

The migration is conventional and reversible:

```php
Schema::table('users', function (Blueprint $table) {
    $table->string('timezone')->default('UTC');
});
```

When registration is selected, the installer:

- makes `timezone` fillable on `App\Models\User` (supporting both Laravel's usual `$fillable` property and Chisel's `#[Fillable]` attribute);
- validates registration and profile submissions against `DateTimeZone::listIdentifiers()`;
- stores the submitted registration timezone; and
- renders dropdowns populated with IANA timezone names.

The profile field uses Livewire's normal model binding. Its Detect button dispatches a bubbling `change` event after setting the browser-detected zone so Livewire receives the update.

## Development

```bash
composer install
composer format
composer test
```

The test suite verifies each source transformation and the non-interactive installer. GitHub Actions tests supported Laravel 12 and 13 releases on PHP 8.3, 8.4, and 8.5, and checks formatting before running Pest.

## Release checklist

Before tagging a release, test against a freshly scaffolded Livewire starter kit for every Laravel version listed above. The installer deliberately relies on starter-kit file structure, so a starter-kit template change is a compatibility event and should be covered with a fixture and CI matrix entry before being advertised as supported.

## License

The MIT License (MIT). See [LICENSE.md](LICENSE.md).

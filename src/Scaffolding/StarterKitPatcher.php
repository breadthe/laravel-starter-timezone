<?php

namespace Breadthe\StarterTimezone\Scaffolding;

class StarterKitPatcher
{
    public function userModel(string $contents): string
    {
        if (str_contains($contents, "'timezone'")) {
            return $contents;
        }

        $updated = preg_replace(
            '/#\[Fillable\(\[(.*?)\]\)\]/s',
            "#[Fillable([$1, 'timezone'])]",
            $contents,
            1,
            $replacements,
        );

        if ($replacements === 1) {
            return $updated;
        }

        $updated = preg_replace(
            '/(protected \$fillable\s*=\s*\[)(.*?)(\n\s*\];)/s',
            "$1$2\n        'timezone',$3",
            $contents,
            1,
            $replacements,
        );

        if ($replacements === 1) {
            return $updated;
        }

        throw new UnsupportedStarterKit('Could not find the User model fillable attributes.');
    }

    public function createNewUser(string $contents): string
    {
        if (str_contains($contents, "'timezone'")) {
            return $contents;
        }

        if (! str_contains($contents, '$this->profileRules(')) {
            $validationStart = strpos($contents, 'Validator::make($input, [');
            $validationEnd = strpos($contents, '])->validate()', $validationStart ?: 0);

            if ($validationStart === false || $validationEnd === false) {
                throw new UnsupportedStarterKit('Could not find Fortify registration validation.');
            }

            $validation = substr($contents, $validationStart, $validationEnd - $validationStart);
            $validation = $this->insertAfterEmail(
                $validation,
                "            'timezone' => ['required', 'string', \\Illuminate\\Validation\\Rule::in(\\DateTimeZone::listIdentifiers())],\n",
                'Fortify registration validation',
            );

            $contents = substr($contents, 0, $validationStart).$validation.substr($contents, $validationEnd);
        }

        $creationStart = strpos($contents, 'return User::create([');

        if ($creationStart === false) {
            throw new UnsupportedStarterKit('Could not find Fortify user creation.');
        }

        $creation = substr($contents, $creationStart);
        $creation = $this->insertAfterEmail(
            $creation,
            "            'timezone' => \$input['timezone'],\n",
            'Fortify user creation',
        );

        return substr($contents, 0, $creationStart).$creation;
    }

    public function registrationView(string $contents): string
    {
        if (str_contains($contents, 'name="timezone"')) {
            return $contents;
        }

        $timezoneField = <<<'BLADE'
            <flux:select
                name="timezone"
                :label="__('Timezone')"
                :value="old('timezone', 'UTC')"
                required
                x-init="
                    const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

                    if (timezone && [...$el.options].some((option) => option.value === timezone)) {
                        $el.value = timezone;
                    }
                "
            >
                @foreach (\DateTimeZone::listIdentifiers() as $timezone)
                    <option value="{{ $timezone }}" @selected(old('timezone', 'UTC') === $timezone)>
                        {{ $timezone }}
                    </option>
                @endforeach
            </flux:select>

BLADE;

        return $this->insertBefore($contents, '<!-- Password -->', $timezoneField, 'the registration password field');
    }

    public function profileView(string $contents): string
    {
        if (str_contains($contents, 'wire:model="timezone"')) {
            return $contents;
        }

        $contents = $this->insertAfter(
            $contents,
            'public string $email = \'\';',
            "\n    public string \$timezone = '';",
            'the profile email property',
        );
        $contents = $this->insertAfter(
            $contents,
            '$this->email = Auth::user()->email;',
            "\n        \$this->timezone = Auth::user()->timezone;",
            'the profile email assignment',
        );

        if (! str_contains($contents, '$this->profileRules(')) {
            $validationStart = strpos($contents, '$this->validate([');
            $validationEnd = strpos($contents, ']);', $validationStart ?: 0);

            if ($validationStart === false || $validationEnd === false) {
                throw new UnsupportedStarterKit('Could not find profile validation.');
            }

            $validation = substr($contents, $validationStart, $validationEnd - $validationStart);
            $validation = $this->insertAfterEmail(
                $validation,
                "            'timezone' => ['required', 'string', \\Illuminate\\Validation\\Rule::in(\\DateTimeZone::listIdentifiers())],\n",
                'profile validation',
            );

            $contents = substr($contents, 0, $validationStart).$validation.substr($contents, $validationEnd);
        }

        $timezoneField = <<<'BLADE'
            <div x-data class="flex items-end gap-2">
                <div class="min-w-0 flex-1">
                    <flux:select wire:model="timezone" x-ref="timezone" :label="__('Timezone')" required>
                        @foreach (\DateTimeZone::listIdentifiers() as $timezone)
                            <flux:select.option :value="$timezone">{{ $timezone }}</flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                <flux:button
                    type="button"
                    variant="outline"
                    x-on:click="
                        const timezone = Intl.DateTimeFormat().resolvedOptions().timeZone;

                        if ([...$refs.timezone.options].some((option) => option.value === timezone)) {
                            $refs.timezone.value = timezone;
                            $refs.timezone.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                    "
                >
                    {{ __('Detect') }}
                </flux:button>
            </div>

BLADE;

        return $this->insertBefore($contents, '<div class="flex items-center gap-4">', $timezoneField, 'the profile submit controls');
    }

    public function profileValidationRules(string $contents): string
    {
        if (str_contains($contents, "'timezone'")) {
            return $contents;
        }

        return $this->insertAfter(
            $contents,
            "'email' => \$this->emailRules(\$userId),",
            "\n            'timezone' => ['required', 'string', \\Illuminate\\Validation\\Rule::in(\\DateTimeZone::listIdentifiers())],",
            'the profile email validation rule',
        );
    }

    private function insertAfterEmail(string $contents, string $insertion, string $description): string
    {
        $updated = preg_replace('/^(\s*\'email\'\s*=>.*\R)/m', "$1$insertion", $contents, 1, $replacements);

        if ($replacements !== 1) {
            throw new UnsupportedStarterKit("Could not find {$description}'s email field.");
        }

        return $updated;
    }

    private function insertAfter(string $contents, string $needle, string $insertion, string $description): string
    {
        $position = strpos($contents, $needle);

        if ($position === false) {
            throw new UnsupportedStarterKit("Could not find {$description}.");
        }

        $offset = $position + strlen($needle);

        return substr($contents, 0, $offset).$insertion.substr($contents, $offset);
    }

    private function insertBefore(string $contents, string $needle, string $insertion, string $description): string
    {
        $position = strpos($contents, $needle);

        if ($position === false) {
            throw new UnsupportedStarterKit("Could not find {$description}.");
        }

        return substr($contents, 0, $position).$insertion.substr($contents, $position);
    }
}

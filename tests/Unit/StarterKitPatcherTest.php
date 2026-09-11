<?php

use Breadthe\StarterTimezone\Scaffolding\StarterKitPatcher;
use Breadthe\StarterTimezone\Scaffolding\UnsupportedStarterKit;

beforeEach(function () {
    $this->patcher = new StarterKitPatcher;
});

test('it adds timezone to a conventional user fillable array', function () {
    $contents = <<<'PHP'
class User
{
    protected $fillable = [
        'name',
        'email',
        'password',
    ];
}
PHP;

    expect($this->patcher->userModel($contents))
        ->toContain("'timezone',")
        ->and($this->patcher->userModel($contents))->toContain("'password',");
});

test('it adds timezone to a chisel fillable attribute', function () {
    $contents = <<<'PHP'
#[Fillable(['name', 'email', 'password'])]
class User {}
PHP;

    expect($this->patcher->userModel($contents))->toContain("#[Fillable(['name', 'email', 'password', 'timezone'])]");
});

test('it adds timezone validation and persistence to Fortify registration', function () {
    $contents = <<<'PHP'
Validator::make($input, [
    'name' => ['required'],
    'email' => ['required', 'email'],
    'password' => ['required'],
])->validate();

return User::create([
    'name' => $input['name'],
    'email' => $input['email'],
    'password' => Hash::make($input['password']),
]);
PHP;

    $patched = $this->patcher->createNewUser($contents);

    expect($patched)
        ->toContain("'timezone' => ['required', 'string', \\Illuminate\\Validation\\Rule::in(\\DateTimeZone::listIdentifiers())],")
        ->toContain("'timezone' => \$input['timezone'],");
});

test('it uses shared profile validation rules when Fortify is configured to use them', function () {
    $contents = <<<'PHP'
Validator::make($input, [
    ...$this->profileRules(),
])->validate();

return User::create([
    'email' => $input['email'],
]);
PHP;

    expect($this->patcher->createNewUser($contents))
        ->toContain("'timezone' => \$input['timezone'],")
        ->not->toContain("'timezone' => ['required'");
});

test('it adds timezone validation to shared profile rules', function () {
    $contents = <<<'PHP'
protected function profileRules(?int $userId = null): array
{
    return [
        'email' => $this->emailRules($userId),
    ];
}
PHP;

    expect($this->patcher->profileValidationRules($contents))
        ->toContain("'timezone' => ['required', 'string', \\Illuminate\\Validation\\Rule::in(\\DateTimeZone::listIdentifiers())],");
});

test('it adds a detected timezone field to the registration view', function () {
    $contents = <<<'BLADE'
<form>
    <!-- Password -->
    <flux:input name="password" />
</form>
BLADE;

    expect($this->patcher->registrationView($contents))
        ->toContain('name="timezone"')
        ->toContain('Intl.DateTimeFormat().resolvedOptions().timeZone');
});

test('it adds a timezone field and validation to the Livewire profile view', function () {
    $contents = <<<'BLADE'
<?php
new class {
    public string $name = '';
    public string $email = '';

    public function mount(): void
    {
        $this->email = Auth::user()->email;
    }

    public function updateProfileInformation(): void
    {
        $this->validate([
            'name' => ['required'],
            'email' => ['required', 'email'],
        ]);
    }
};
?>
<form>
    <div class="flex items-center gap-4">
        <button>Save</button>
    </div>
</form>
BLADE;

    $patched = $this->patcher->profileView($contents);

    expect($patched)
        ->toContain('public string $timezone')
        ->toContain('$this->timezone = Auth::user()->timezone;')
        ->toContain('wire:model="timezone"')
        ->toContain("'timezone' => ['required', 'string', \\Illuminate\\Validation\\Rule::in(\\DateTimeZone::listIdentifiers())],");
});

test('it stops when the target does not resemble a supported starter kit', function () {
    $this->patcher->registrationView('<form></form>');
})->throws(UnsupportedStarterKit::class);

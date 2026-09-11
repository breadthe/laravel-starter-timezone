<?php

namespace Breadthe\StarterTimezone\Scaffolding;

class ScaffoldResult
{
    /**
     * @param  array<int, string>  $changes
     * @param  array<int, string>  $warnings
     * @param  array<int, string>  $errors
     */
    public function __construct(
        public array $changes = [],
        public array $warnings = [],
        public array $errors = [],
    ) {}

    public function failed(): bool
    {
        return $this->errors !== [];
    }
}

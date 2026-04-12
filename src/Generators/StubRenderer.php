<?php

declare(strict_types=1);

namespace ImranAhmedOptilius\DddMaker\Generators;

use RuntimeException;

class StubRenderer
{
    private string $stubPath;

    public function __construct()
    {
        $this->stubPath = dirname(__DIR__) . '/Stubs';
    }

    /**
     * Render a named stub with the given variables.
     *
     * @param  array<string, mixed> $vars
     */
    public function render(string $stub, array $vars): string
    {
        $file = "{$this->stubPath}/{$stub}.stub";

        if (! file_exists($file)) {
            throw new RuntimeException("Stub not found: {$file}");
        }

        $content = (string) file_get_contents($file);

        foreach ($vars as $key => $value) {
            if (is_string($value) || is_int($value)) {
                $content = str_replace("{{ {$key} }}", (string) $value, $content);
            }
        }

        return $content;
    }
}

<?php

declare(strict_types=1);

namespace ImranAhmedOptilius\DddMaker\Generators;

use Illuminate\Filesystem\Filesystem;

class FeatureGenerator
{
    public function __construct(
        private readonly Filesystem $files,
        private readonly StubRenderer $renderer,
    ) {}

    /**
     * Generate all feature files and return a status report.
     *
     * @param  string        $prefix
     * @param  string        $folder
     * @param  string|null   $requestName     null = skip Request generation
     * @param  string[]      $responses
     * @param  string[]      $outputs
     * @param  string[]      $repositories
     * @param  string[]      $voNames         Value Object class names
     * @param  string|null   $entityName      null = skip Entity generation
     * @param  string|null   $modelName       null = skip Eloquent Model generation
     * @param  string|null   $repoInputName   null = skip Repository Input DTO
     * @param  string|null   $servInputName   null = skip Service Input DTO
     * @return array<int, array{path: string, created: bool}>
     */
    public function generate(
        string $prefix,
        string $folder,
        ?string $requestName,
        array $responses,
        array $outputs,
        array $repositories,
        array $voNames = [],
        ?string $entityName = null,
        ?string $modelName = null,
        ?string $repoInputName = null,
        ?string $servInputName = null,
    ): array {
        $results = [];

        $vars = [
            'prefix'          => $prefix,
            'folder'          => $folder,
            'requestName'     => $requestName ?? '',
            'primaryRepo'     => $repositories[0],
            'primaryOutput'   => $outputs[0],
            'primaryResponse' => $responses[0],
            'repoInputName'   => $repoInputName ?? '',
            'servInputName'   => $servInputName ?? '',
            'entityName'      => $entityName ?? '',
            'modelName'       => $modelName ?? '',
        ];

        // ── A. Request (optional) ──────────────────────────────────────────
        if ($requestName !== null) {
            $results[] = $this->make(
                "app/Http/Requests/Api/V1/{$folder}/{$requestName}.php",
                'request',
                $vars
            );
        }

        // ── B. Action ──────────────────────────────────────────────────────
        $results[] = $this->make(
            "app/Http/Controllers/Api/V1/{$folder}/{$prefix}Action.php",
            $requestName !== null ? 'action' : 'action-no-request',
            $vars
        );

        // ── C. UseCase interface + implementation ──────────────────────────
        $results[] = $this->make(
            "app/UseCases/{$folder}/I{$prefix}UseCase.php",
            'usecase-interface',
            $vars
        );
        $results[] = $this->make(
            "app/UseCases/{$folder}/{$prefix}UseCase.php",
            'usecase',
            $vars
        );

        // ── D. Domain Service interface + Infra implementation ─────────────
        $results[] = $this->make(
            "app/Domain/{$folder}/Services/I{$prefix}Service.php",
            'service-interface',
            $vars
        );
        $results[] = $this->make(
            "app/Infra/{$folder}/Services/{$prefix}Service.php",
            'service',
            $vars
        );

        // ── D-opt. Service Input DTO ───────────────────────────────────────
        if ($servInputName !== null) {
            $results[] = $this->make(
                "app/Domain/{$folder}/Services/Input/{$servInputName}.php",
                'service-input',
                array_merge($vars, ['servInputName' => $servInputName])
            );
        }

        // ── E. Repositories ────────────────────────────────────────────────
        foreach ($repositories as $repo) {
            $repoVars = array_merge($vars, ['repoName' => $repo]);

            $results[] = $this->make(
                "app/Domain/{$folder}/Repositories/I{$repo}.php",
                'repository-interface',
                $repoVars
            );
            $results[] = $this->make(
                "app/Infra/{$folder}/Repositories/{$repo}.php",
                'repository',
                $repoVars
            );
        }

        // ── E-opt. Repository Input DTO ────────────────────────────────────
        if ($repoInputName !== null) {
            $results[] = $this->make(
                "app/Domain/{$folder}/Repositories/Input/{$repoInputName}.php",
                'repository-input',
                array_merge($vars, ['repoInputName' => $repoInputName])
            );
        }

        // ── F. Output DTOs ─────────────────────────────────────────────────
        foreach ($outputs as $output) {
            $results[] = $this->make(
                "app/Domain/{$folder}/Services/Output/{$output}.php",
                'output',
                array_merge($vars, ['outputName' => $output])
            );
        }

        // ── G. Responses ───────────────────────────────────────────────────
        foreach ($responses as $response) {
            $responseVars = array_merge($vars, ['responseName' => $response]);

            $results[] = $this->make(
                "app/Http/Responses/Api/V1/{$folder}/I{$response}.php",
                'response-interface',
                $responseVars
            );
            $results[] = $this->make(
                "app/Http/Responses/Api/V1/{$folder}/{$response}.php",
                'response',
                $responseVars
            );
        }

        // ── H. Value Objects (optional) ────────────────────────────────────
        foreach ($voNames as $vo) {
            $results[] = $this->make(
                "app/Domain/{$folder}/Vo/{$vo}.php",
                'value-object',
                array_merge($vars, ['voName' => $vo])
            );
        }

        // ── I. Entity (optional) ───────────────────────────────────────────
        if ($entityName !== null) {
            $results[] = $this->make(
                "app/Models/Entities/{$entityName}.php",
                'entity',
                array_merge($vars, ['entityName' => $entityName])
            );
        }

        // ── J. Eloquent Model (optional) ───────────────────────────────────
        if ($modelName !== null) {
            $results[] = $this->make(
                "app/Models/{$modelName}.php",
                'model',
                array_merge($vars, ['modelName' => $modelName])
            );
        }

        return $results;
    }

    /**
     * Render a stub and write it to disk (skips if file already exists).
     *
     * @param  array<string, mixed> $vars
     * @return array{path: string, created: bool}
     */
    private function make(string $path, string $stub, array $vars): array
    {
        $fullPath = base_path($path);

        if ($this->files->exists($fullPath)) {
            return ['path' => $path, 'created' => false];
        }

        $this->files->ensureDirectoryExists(dirname($fullPath));
        $this->files->put($fullPath, $this->renderer->render($stub, $vars));

        return ['path' => $path, 'created' => true];
    }
}

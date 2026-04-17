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
     * @param  string|null   $requestName        null = skip Request generation
     * @param  string[]      $responses
     * @param  bool          $generateResponses   false = reuse existing, skip file creation
     * @param  string[]      $outputs
     * @param  bool          $generateOutputs     false = reuse existing, skip file creation
     * @param  string[]      $repositories
     * @param  bool          $generateRepositories false = reuse existing, skip file creation
     * @param  string[]      $voNames
     * @param  string|null   $entityName
     * @param  string|null   $modelName
     * @param  string|null   $repoInputName
     * @param  string|null   $servInputName
     * @return array<int, array{path: string, created: bool, skipped_existing: bool}>
     */
    public function generate(
        string $prefix,
        string $folder,
        ?string $requestName,
        array $responses,
        bool $generateResponses,
        array $outputs,
        bool $generateOutputs,
        array $repositories,
        bool $generateRepositories,
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

        // A. Request (optional)
        if ($requestName !== null) {
            $results[] = $this->make(
                "app/Http/Requests/Api/V1/{$folder}/{$requestName}.php",
                'request',
                $vars
            );
        }

        // B. Action — choose stub based on whether a request exists
        $results[] = $this->make(
            "app/Http/Controllers/Api/V1/{$folder}/{$prefix}Action.php",
            $requestName !== null ? 'action' : 'action-no-request',
            $vars
        );

        // C. UseCase interface + implementation
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

        // D. Domain Service interface + Infra implementation
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

        // D-opt. Service Input DTO
        if ($servInputName !== null) {
            $results[] = $this->make(
                "app/Domain/{$folder}/Services/Input/{$servInputName}.php",
                'service-input',
                array_merge($vars, ['servInputName' => $servInputName])
            );
        }

        // E. Repositories — generate new or mark as reused existing
        foreach ($repositories as $repo) {
            $repoVars = array_merge($vars, ['repoName' => $repo]);

            if ($generateRepositories) {
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
            } else {
                $results[] = $this->reused("(existing) {$repo}");
            }
        }

        // E-opt. Repository Input DTO
        if ($repoInputName !== null) {
            $results[] = $this->make(
                "app/Domain/{$folder}/Repositories/Input/{$repoInputName}.php",
                'repository-input',
                array_merge($vars, ['repoInputName' => $repoInputName])
            );
        }

        // F. Output DTOs — generate new or mark as reused existing
        foreach ($outputs as $output) {
            if ($generateOutputs) {
                $results[] = $this->make(
                    "app/Domain/{$folder}/Services/Output/{$output}.php",
                    'output',
                    array_merge($vars, ['outputName' => $output])
                );
            } else {
                $results[] = $this->reused("(existing) {$output}");
            }
        }

        // G. Responses — generate new or mark as reused existing
        foreach ($responses as $response) {
            $responseVars = array_merge($vars, ['responseName' => $response]);

            if ($generateResponses) {
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
            } else {
                $results[] = $this->reused("(existing) {$response}");
            }
        }

        // H. Value Objects (optional)
        foreach ($voNames as $vo) {
            $results[] = $this->make(
                "app/Domain/{$folder}/Vo/{$vo}.php",
                'value-object',
                array_merge($vars, ['voName' => $vo])
            );
        }

        // I. Entity (optional)
        if ($entityName !== null) {
            $results[] = $this->make(
                "app/Models/Entities/{$entityName}.php",
                'entity',
                array_merge($vars, ['entityName' => $entityName])
            );
        }

        // J. Eloquent Model (optional)
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
     * @return array{path: string, created: bool, skipped_existing: bool}
     */
    private function make(string $path, string $stub, array $vars): array
    {
        $fullPath = base_path($path);

        if ($this->files->exists($fullPath)) {
            return ['path' => $path, 'created' => false, 'skipped_existing' => false];
        }

        $this->files->ensureDirectoryExists(dirname($fullPath));
        $this->files->put($fullPath, $this->renderer->render($stub, $vars));

        return ['path' => $path, 'created' => true, 'skipped_existing' => false];
    }

    /**
     * Return a result entry for a reused (existing) class — no file written.
     *
     * @return array{path: string, created: bool, skipped_existing: bool}
     */
    private function reused(string $label): array
    {
        return ['path' => $label, 'created' => false, 'skipped_existing' => true];
    }
}

<?php

declare(strict_types=1);

namespace ImranAhmedOptilius\DddMaker\Commands;

use Illuminate\Console\Command;
use ImranAhmedOptilius\DddMaker\Generators\FeatureGenerator;

class MakeDddCommand extends Command
{
    protected $signature = 'make:ddd
                            {--prefix= : File prefix (e.g. ForHomePageTeacherGet)}
                            {--folder= : Feature folder name (e.g. HomePage or MembershipStatus/History)}';

    protected $description = 'Scaffold a Clean Architecture + DDD action chain (Action, UseCase, Service, Repository, Output DTO, Response) with optional Request, VO, Entity, and Model';

    public function handle(FeatureGenerator $generator): int
    {
        $this->displayBanner();

        // 1. Prefix
        $prefix = $this->option('prefix')
            ?? $this->ask('  <fg=cyan>Enter the file prefix</> <fg=gray>(e.g. ForHomePageTeacherGet)</>');

        if (empty($prefix)) {
            $this->error('  Prefix cannot be empty.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->line('  <fg=yellow>── Structure Questions ───────────────────────────────────────</>');
        $this->newLine();

        // 2. Value Objects
        $voNames = [];
        if ($this->confirm('  Does this action need <fg=white>Value Objects (VO)</> (IDs, statuses, types)?', false)) {
            $voNames = $this->gatherList('VO class name', 'VO');
        }

        // 3. Repository Input DTO
        $repoInputName = null;
        if ($this->confirm('  Does the <fg=white>Repository</> need a dedicated Input DTO for its query arguments?', false)) {
            $repoInputName = $this->askWithDefault('  Repository Input DTO name', "{$prefix}RepoInput");
        }

        // 4. Service Input DTO
        $servInputName = null;
        if ($this->confirm('  Does the <fg=white>Service</> need a dedicated Input DTO for its arguments?', false)) {
            $servInputName = $this->askWithDefault('  Service Input DTO name', "{$prefix}ServInput");
        }

        // 5. Response — new or existing
        [$responses, $generateResponses] = $this->gatherNewOrExisting(
            label: 'Response',
            hint: 'formats the JSON output',
            default: "{$prefix}Response",
            singularKey: 'response'
        );

        // 6. Output DTO — new or existing
        [$outputs, $generateOutputs] = $this->gatherNewOrExisting(
            label: 'Output DTO',
            hint: 'DTO returned by Service to UseCase',
            default: "{$prefix}Output",
            singularKey: 'output'
        );

        // 7. Repository — new or existing
        [$repositories, $generateRepositories] = $this->gatherNewOrExisting(
            label: 'Repository',
            hint: 'queries the database',
            default: "{$prefix}Repository",
            singularKey: 'repository'
        );

        // 8. Entity
        $entityName = null;
        if ($this->confirm('  Does this action need a domain <fg=white>Entity</> class?', false)) {
            $entityName = $this->askWithDefault('  Entity class name', "{$prefix}Entity");
        }

        // 9. Eloquent Model
        $modelName = null;
        if ($this->confirm('  Does this action need a new <fg=white>Eloquent Model</>?', false)) {
            $modelName = $this->askWithDefault('  Model class name', $prefix);
        }

        // 10. Request
        $requestName = null;
        if ($this->confirm('  Does this action need a <fg=white>Form Request</> class for validation?', true)) {
            $requestName = $this->askWithDefault('  Request class name', "{$prefix}Request");
        }

        // 11. Feature Folder
        $this->newLine();
        $folder = $this->option('folder')
            ?? $this->ask('  <fg=cyan>Enter the feature folder</> <fg=gray>(e.g. HomePage or MembershipStatus/History)</>');

        if (empty($folder)) {
            $this->error('  Feature folder cannot be empty.');
            return self::FAILURE;
        }

        // Summary
        $this->newLine();
        $this->line('  <fg=yellow>── Files to be created ───────────────────────────────────────</>');
        $this->displayFileSummary(
            prefix: $prefix,
            folder: $folder,
            requestName: $requestName,
            responses: $responses,
            generateResponses: $generateResponses,
            outputs: $outputs,
            generateOutputs: $generateOutputs,
            repositories: $repositories,
            generateRepositories: $generateRepositories,
            voNames: $voNames,
            entityName: $entityName,
            modelName: $modelName,
            repoInputName: $repoInputName,
            servInputName: $servInputName,
        );

        if (! $this->confirm('  <fg=cyan>Proceed with file generation?</>', true)) {
            $this->warn('  Cancelled.');
            return self::SUCCESS;
        }

        // Generate
        $this->newLine();
        $this->line('  <fg=yellow>── Creating files ────────────────────────────────────────────</>');

        $results = $generator->generate(
            prefix: $prefix,
            folder: $folder,
            requestName: $requestName,
            responses: $responses,
            generateResponses: $generateResponses,
            outputs: $outputs,
            generateOutputs: $generateOutputs,
            repositories: $repositories,
            generateRepositories: $generateRepositories,
            voNames: $voNames,
            entityName: $entityName,
            modelName: $modelName,
            repoInputName: $repoInputName,
            servInputName: $servInputName,
        );

        foreach ($results as $result) {
            if ($result['skipped_existing']) {
                $this->line("  <fg=blue>↩ REUSED</>   <fg=gray>{$result['path']}</>");
            } elseif ($result['created']) {
                $this->line("  <fg=green>✔ CREATED</>  <fg=gray>{$result['path']}</>");
            } else {
                $this->line("  <fg=yellow>⚠ SKIPPED</>  <fg=gray>{$result['path']} (already exists)</>");
            }
        }

        // AppServiceProvider bindings
        $this->newLine();
        $this->line('  <fg=yellow>── Add to AppServiceProvider::register() ─────────────────────</>');
        $this->displayBindings(
            prefix: $prefix,
            responses: $responses,
            generateResponses: $generateResponses,
            repositories: $repositories,
            generateRepositories: $generateRepositories,
        );

        // Route hint
        $this->newLine();
        $this->line('  <fg=yellow>── Add to routes/api.php ─────────────────────────────────────</>');
        $endpoint = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $prefix));
        $actionClass = "App\\Http\\Controllers\\Api\\V1\\{$folder}\\{$prefix}Action";
        $this->line("  <fg=gray>Route::get('/{$endpoint}', \\{$actionClass}::class);</>");

        $this->newLine();
        $this->info('  Done! All files created successfully.');
        $this->newLine();

        return self::SUCCESS;
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    /**
     * Ask whether to create new file(s) or reuse an existing class.
     * Returns [names[], shouldGenerate].
     *
     * @return array{0: string[], 1: bool}
     */
    private function gatherNewOrExisting(
        string $label,
        string $hint,
        string $default,
        string $singularKey,
    ): array {
        $this->line("  <fg=white>{$label}</> <fg=gray>— {$hint}</>");

        $choice = $this->choice(
            "  {$label}: create new or reuse an existing class?",
            ['Create new', 'Reuse existing'],
            0
        );

        if ($choice === 'Reuse existing') {
            $this->line("  <fg=gray>  Provide the existing class name(s). They will be imported but not generated.</>");
            $names = $this->gatherList($singularKey, $default);
            $this->newLine();
            return [$names, false];
        }

        $hasCustom = $this->confirm(
            "  Use a custom name or create multiple {$label} classes?",
            false
        );

        if (! $hasCustom) {
            $this->newLine();
            return [[$default], true];
        }

        $names = $this->gatherList($singularKey, $default);
        $this->newLine();
        return [$names, true];
    }

    private function askWithDefault(string $label, string $default): string
    {
        $answer = $this->ask("{$label} <fg=gray>[default: {$default}]</>");
        return empty($answer) ? $default : $answer;
    }

    /**
     * @return string[]
     */
    private function gatherList(string $singularKey, string $fallback): array
    {
        $names = [];

        while (true) {
            $name = $this->ask("  Enter {$singularKey} name <fg=gray>(leave blank to finish)</>");

            if (empty($name)) {
                break;
            }

            $names[] = $name;
            $this->line("  <fg=green>  Added:</> {$name}");
        }

        return empty($names) ? [$fallback] : $names;
    }

    private function displayFileSummary(
        string $prefix,
        string $folder,
        ?string $requestName,
        array $responses,
        bool $generateResponses,
        array $outputs,
        bool $generateOutputs,
        array $repositories,
        bool $generateRepositories,
        array $voNames,
        ?string $entityName,
        ?string $modelName,
        ?string $repoInputName,
        ?string $servInputName,
    ): void {
        $groups = [];

        if ($requestName) {
            $groups['Form Request — new'][] = "app/Http/Requests/Api/V1/{$folder}/{$requestName}.php";
        }

        $groups['Action — new'][] = "app/Http/Controllers/Api/V1/{$folder}/{$prefix}Action.php";

        $groups['Use Case — new'] = [
            "app/UseCases/{$folder}/I{$prefix}UseCase.php",
            "app/UseCases/{$folder}/{$prefix}UseCase.php",
        ];

        $groups['Domain Service — new'] = [
            "app/Domain/{$folder}/Services/I{$prefix}Service.php",
            "app/Infra/{$folder}/Services/{$prefix}Service.php",
        ];

        if ($servInputName) {
            $groups['Service Input DTO — new'][] = "app/Domain/{$folder}/Services/Input/{$servInputName}.php";
        }

        if ($generateRepositories) {
            foreach ($repositories as $repo) {
                $groups['Repository — new'][] = "app/Domain/{$folder}/Repositories/I{$repo}.php";
                $groups['Repository — new'][] = "app/Infra/{$folder}/Repositories/{$repo}.php";
            }
        } else {
            foreach ($repositories as $repo) {
                $groups['Repository — reusing existing'][] = "{$repo}";
            }
        }

        if ($repoInputName) {
            $groups['Repository Input DTO — new'][] = "app/Domain/{$folder}/Repositories/Input/{$repoInputName}.php";
        }

        if ($generateOutputs) {
            foreach ($outputs as $output) {
                $groups['Output DTO — new'][] = "app/Domain/{$folder}/Services/Output/{$output}.php";
            }
        } else {
            foreach ($outputs as $output) {
                $groups['Output DTO — reusing existing'][] = $output;
            }
        }

        if ($generateResponses) {
            foreach ($responses as $response) {
                $groups['Response — new'][] = "app/Http/Responses/Api/V1/{$folder}/I{$response}.php";
                $groups['Response — new'][] = "app/Http/Responses/Api/V1/{$folder}/{$response}.php";
            }
        } else {
            foreach ($responses as $response) {
                $groups['Response — reusing existing'][] = $response;
            }
        }

        foreach ($voNames as $vo) {
            $groups['Value Object — new'][] = "app/Domain/{$folder}/Vo/{$vo}.php";
        }

        if ($entityName) {
            $groups['Entity — new'][] = "app/Models/Entities/{$entityName}.php";
        }

        if ($modelName) {
            $groups['Eloquent Model — new'][] = "app/Models/{$modelName}.php";
        }

        foreach ($groups as $groupName => $files) {
            $color = str_contains($groupName, 'reusing') ? 'blue' : 'yellow';
            $this->line("  <fg={$color}>  {$groupName}:</>");
            foreach ($files as $file) {
                $this->line("  <fg=gray>    • {$file}</>");
            }
        }

        $this->newLine();
    }

    private function displayBindings(
        string $prefix,
        array $responses,
        bool $generateResponses,
        array $repositories,
        bool $generateRepositories,
    ): void {
        $this->line("  <fg=gray>  // Use Cases</>");
        $this->line("  <fg=gray>  \$this->app->bind(I{$prefix}UseCase::class, {$prefix}UseCase::class);</>");
        $this->newLine();
        $this->line("  <fg=gray>  // Domain Services</>");
        $this->line("  <fg=gray>  \$this->app->bind(I{$prefix}Service::class, {$prefix}Service::class);</>");

        if ($generateRepositories) {
            $this->newLine();
            $this->line("  <fg=gray>  // Repositories</>");
            foreach ($repositories as $repo) {
                $this->line("  <fg=gray>  \$this->app->bind(I{$repo}::class, {$repo}::class);</>");
            }
        }

        if ($generateResponses) {
            $this->newLine();
            $this->line("  <fg=gray>  // Responses</>");
            foreach ($responses as $response) {
                $this->line("  <fg=gray>  \$this->app->bind(I{$response}::class, {$response}::class);</>");
            }
        }
    }

    private function displayBanner(): void
    {
        $this->newLine();
        $this->line('  <fg=magenta>╔═══════════════════════════════════════════════════╗</>');
        $this->line('  <fg=magenta>║</>  <fg=white;options=bold>Laravel DDD Maker</>  <fg=gray>by imran-ahmed-optilius</>      <fg=magenta>║</>');
        $this->line('  <fg=magenta>║</>  <fg=gray>Clean Architecture + Domain-Driven Design</>      <fg=magenta>║</>');
        $this->line('  <fg=magenta>╚═══════════════════════════════════════════════════╝</>');
        $this->newLine();
    }
}

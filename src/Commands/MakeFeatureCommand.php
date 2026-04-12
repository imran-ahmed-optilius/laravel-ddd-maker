<?php

declare(strict_types=1);

namespace ImranAhmedOptilius\DddMaker\Commands;

use Illuminate\Console\Command;
use ImranAhmedOptilius\DddMaker\Generators\FeatureGenerator;

class MakeFeatureCommand extends Command
{
    protected $signature = 'make:feature
                            {--prefix= : File prefix (e.g. ForHomePageTeacherGet)}
                            {--folder= : Feature folder name (e.g. HomePage or MembershipStatus/History)}';

    protected $description = 'Scaffold a full Clean Architecture + DDD feature (Action, UseCase, Service, Repository, Output DTO, Response, and optional Request, VO, Entity, Model, Input DTOs)';

    public function handle(FeatureGenerator $generator): int
    {
        $this->displayBanner();

        // ── 1. Prefix ──────────────────────────────────────────────────────
        $prefix = $this->option('prefix')
            ?? $this->ask('  <fg=cyan>Enter the file prefix</> <fg=gray>(e.g. ForHomePageTeacherGet)</>');

        if (empty($prefix)) {
            $this->error('  Prefix cannot be empty.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->line('  <fg=yellow>── Complexity & Naming Questions ─────────────────────────────</>');
        $this->newLine();

        // ── 2. Value Objects ───────────────────────────────────────────────
        $voNames = [];
        if ($this->confirm('  Will this feature require <fg=white>Value Objects (VO)</> for IDs, status, or types?', false)) {
            $voNames = $this->gatherList('VO class name', 'VO');
        }

        // ── 3. Repository Input DTO ────────────────────────────────────────
        $repoInputName = null;
        if ($this->confirm('  Will the <fg=white>Repository</> require a dedicated Input DTO for its arguments?', false)) {
            $repoInputName = $this->askWithDefault('  Repository Input DTO name', "{$prefix}RepoInput");
        }

        // ── 4. Service Input DTO ───────────────────────────────────────────
        $servInputName = null;
        if ($this->confirm('  Will the <fg=white>Service</> require a dedicated Input DTO for its arguments?', false)) {
            $servInputName = $this->askWithDefault('  Service Input DTO name', "{$prefix}ServInput");
        }

        // ── 5. Response name(s) ────────────────────────────────────────────
        $responses = $this->gatherMultipleNames(
            label: '  Will the <fg=white>Response</> have a different or multiple names?',
            default: "{$prefix}Response",
            singularKey: 'response'
        );

        // ── 6. Output DTO name(s) ──────────────────────────────────────────
        $outputs = $this->gatherMultipleNames(
            label: '  Will the <fg=white>Output (DTO)</> have a different or multiple names?',
            default: "{$prefix}Output",
            singularKey: 'output'
        );

        // ── 7. Repository name(s) ──────────────────────────────────────────
        $repositories = $this->gatherMultipleNames(
            label: '  Will the <fg=white>Repository</> have a different or multiple names?',
            default: "{$prefix}Repository",
            singularKey: 'repository'
        );

        // ── 8. Entity ──────────────────────────────────────────────────────
        $entityName = null;
        if ($this->confirm('  Is there a need for a domain <fg=white>Entity</> class?', false)) {
            $entityName = $this->askWithDefault('  Entity class name', "{$prefix}Entity");
        }

        // ── 9. Eloquent Model ──────────────────────────────────────────────
        $modelName = null;
        if ($this->confirm('  Should an <fg=white>Eloquent Model</> be generated?', false)) {
            $modelName = $this->askWithDefault('  Model class name', $prefix);
        }

        // ── 10. Request ────────────────────────────────────────────────────
        $requestName = null;
        if ($this->confirm('  Will this feature need a <fg=white>Request</> (Form Request) class?', true)) {
            $requestName = $this->askWithDefault('  Request class name', "{$prefix}Request");
        }

        // ── 11. Feature Folder ─────────────────────────────────────────────
        $this->newLine();
        $folder = $this->option('folder')
            ?? $this->ask('  <fg=cyan>Enter the feature folder name</> <fg=gray>(e.g. HomePage or MembershipStatus/History)</>');

        if (empty($folder)) {
            $this->error('  Feature folder cannot be empty.');
            return self::FAILURE;
        }

        // ── Summary ───────────────────────────────────────────────────────
        $this->newLine();
        $this->line('  <fg=yellow>── Files to be generated ─────────────────────────────────────</>');
        $this->displayFileSummary(
            prefix: $prefix,
            folder: $folder,
            requestName: $requestName,
            responses: $responses,
            outputs: $outputs,
            repositories: $repositories,
            voNames: $voNames,
            entityName: $entityName,
            modelName: $modelName,
            repoInputName: $repoInputName,
            servInputName: $servInputName,
        );

        if (! $this->confirm('  <fg=cyan>Generate all files now?</>', true)) {
            $this->warn('  Cancelled.');
            return self::SUCCESS;
        }

        // ── Generate ──────────────────────────────────────────────────────
        $this->newLine();
        $this->line('  <fg=yellow>── Generating ────────────────────────────────────────────────</>');

        $results = $generator->generate(
            prefix: $prefix,
            folder: $folder,
            requestName: $requestName,
            responses: $responses,
            outputs: $outputs,
            repositories: $repositories,
            voNames: $voNames,
            entityName: $entityName,
            modelName: $modelName,
            repoInputName: $repoInputName,
            servInputName: $servInputName,
        );

        foreach ($results as $result) {
            if ($result['created']) {
                $this->line("  <fg=green>✔ CREATED</>  <fg=gray>{$result['path']}</>");
            } else {
                $this->line("  <fg=yellow>⚠ SKIPPED</>  <fg=gray>{$result['path']} (already exists)</>");
            }
        }

        // ── AppServiceProvider bindings ────────────────────────────────────
        $this->newLine();
        $this->line('  <fg=yellow>── Add to AppServiceProvider::register() ─────────────────────</>');
        $this->displayBindings($prefix, $responses, $repositories);

        // ── Route hint ─────────────────────────────────────────────────────
        $this->newLine();
        $this->line('  <fg=yellow>── Add to routes/api.php ─────────────────────────────────────</>');
        $endpoint = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $prefix));
        $actionClass = "App\\Http\\Controllers\\Api\\V1\\{$folder}\\{$prefix}Action";
        $this->line("  <fg=gray>Route::get('/{$endpoint}', \\{$actionClass}::class);</>");

        $this->newLine();
        $this->info('  Done! All files generated successfully.');
        $this->newLine();

        return self::SUCCESS;
    }

    // ──────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────

    /**
     * Ask with a default, returning the default if empty answer is given.
     */
    private function askWithDefault(string $label, string $default): string
    {
        $answer = $this->ask("{$label} <fg=gray>[default: {$default}]</>");
        return empty($answer) ? $default : $answer;
    }

    /**
     * Ask yes/no, then collect multiple names one by one.
     * Returns [$default] if the user declines or enters nothing.
     */
    private function gatherMultipleNames(string $label, string $default, string $singularKey): array
    {
        $hasCustom = $this->confirm("{$label}", false);

        if (! $hasCustom) {
            return [$default];
        }

        return $this->gatherList($singularKey, $default);
    }

    /**
     * Collect a list of names one by one until the user leaves blank.
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
        array $outputs,
        array $repositories,
        array $voNames,
        ?string $entityName,
        ?string $modelName,
        ?string $repoInputName,
        ?string $servInputName,
    ): void {
        $groups = [];

        if ($requestName) {
            $groups['Request'][] = "app/Http/Requests/Api/V1/{$folder}/{$requestName}.php";
        }

        $groups['Action'][] = "app/Http/Controllers/Api/V1/{$folder}/{$prefix}Action.php";

        $groups['Use Case'] = [
            "app/UseCases/{$folder}/I{$prefix}UseCase.php",
            "app/UseCases/{$folder}/{$prefix}UseCase.php",
        ];

        $groups['Domain Service'] = [
            "app/Domain/{$folder}/Services/I{$prefix}Service.php",
            "app/Infra/{$folder}/Services/{$prefix}Service.php",
        ];

        if ($servInputName) {
            $groups['Service Input DTO'][] = "app/Domain/{$folder}/Services/Input/{$servInputName}.php";
        }

        foreach ($repositories as $repo) {
            $groups['Repository'][] = "app/Domain/{$folder}/Repositories/I{$repo}.php";
            $groups['Repository'][] = "app/Infra/{$folder}/Repositories/{$repo}.php";
        }

        if ($repoInputName) {
            $groups['Repository Input DTO'][] = "app/Domain/{$folder}/Repositories/Input/{$repoInputName}.php";
        }

        foreach ($outputs as $output) {
            $groups['Output DTO'][] = "app/Domain/{$folder}/Services/Output/{$output}.php";
        }

        foreach ($responses as $response) {
            $groups['Response'][] = "app/Http/Responses/Api/V1/{$folder}/I{$response}.php";
            $groups['Response'][] = "app/Http/Responses/Api/V1/{$folder}/{$response}.php";
        }

        foreach ($voNames as $vo) {
            $groups['Value Objects'][] = "app/Domain/{$folder}/Vo/{$vo}.php";
        }

        if ($entityName) {
            $groups['Entity'][] = "app/Models/Entities/{$entityName}.php";
        }

        if ($modelName) {
            $groups['Eloquent Model'][] = "app/Models/{$modelName}.php";
        }

        foreach ($groups as $groupName => $files) {
            $this->line("  <fg=yellow>  {$groupName}:</>");
            foreach ($files as $file) {
                $this->line("  <fg=gray>    • {$file}</>");
            }
        }

        $this->newLine();
    }

    private function displayBindings(
        string $prefix,
        array $responses,
        array $repositories,
    ): void {
        $this->line("  <fg=gray>  // Use Cases</>");
        $this->line("  <fg=gray>  \$this->app->bind(I{$prefix}UseCase::class, {$prefix}UseCase::class);</>");
        $this->newLine();
        $this->line("  <fg=gray>  // Domain Services</>");
        $this->line("  <fg=gray>  \$this->app->bind(I{$prefix}Service::class, {$prefix}Service::class);</>");
        $this->newLine();
        $this->line("  <fg=gray>  // Repositories</>");
        foreach ($repositories as $repo) {
            $this->line("  <fg=gray>  \$this->app->bind(I{$repo}::class, {$repo}::class);</>");
        }
        $this->newLine();
        $this->line("  <fg=gray>  // Responses</>");
        foreach ($responses as $response) {
            $this->line("  <fg=gray>  \$this->app->bind(I{$response}::class, {$response}::class);</>");
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

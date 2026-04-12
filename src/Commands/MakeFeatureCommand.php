<?php

declare(strict_types=1);

namespace ImranAhmedOptilius\DddMaker\Commands;

use Illuminate\Console\Command;
use ImranAhmedOptilius\DddMaker\Generators\FeatureGenerator;

class MakeFeatureCommand extends Command
{
    protected $signature = 'make:feature
                            {--prefix= : File prefix (e.g. ForHomePageTeacherGet)}
                            {--folder= : Feature folder name (e.g. HomePage)}';

    protected $description = 'Scaffold a full Clean Architecture + DDD feature (Request, Action, UseCase, Service, Repository, Output DTO, Response)';

    public function handle(FeatureGenerator $generator): int
    {
        $this->displayBanner();

        // ── Prefix ─────────────────────────────────────────────────────────
        $prefix = $this->option('prefix')
            ?? $this->ask('  <fg=cyan>Enter the file prefix</> <fg=gray>(e.g. ForHomePageTeacherGet)</>');

        if (empty($prefix)) {
            $this->error('  Prefix cannot be empty.');
            return self::FAILURE;
        }

        // ── Feature folder ─────────────────────────────────────────────────
        $folder = $this->option('folder')
            ?? $this->ask('  <fg=cyan>Enter the feature folder name</> <fg=gray>(e.g. HomePage)</>');

        if (empty($folder)) {
            $this->error('  Feature folder cannot be empty.');
            return self::FAILURE;
        }

        $this->newLine();
        $this->line('  <fg=yellow>── Custom Names ──────────────────────────────────────────────</>');
        $this->line("  <fg=gray>Press ENTER to accept the default</> <fg=white>{$prefix}*</> <fg=gray>naming.</>");
        $this->newLine();

        // ── Request ────────────────────────────────────────────────────────
        $requestName = $this->gatherSingleName(
            label: "  Request class",
            default: "{$prefix}Request"
        );

        // ── Responses ─────────────────────────────────────────────────────
        $responses = $this->gatherMultipleNames(
            label: "  Response(s)",
            default: "{$prefix}Response",
            singularKey: 'response'
        );

        // ── Outputs (DTOs) ────────────────────────────────────────────────
        $outputs = $this->gatherMultipleNames(
            label: "  Output DTO(s)",
            default: "{$prefix}Output",
            singularKey: 'output'
        );

        // ── Repositories ──────────────────────────────────────────────────
        $repositories = $this->gatherMultipleNames(
            label: "  Repository(ies)",
            default: "{$prefix}Repository",
            singularKey: 'repository'
        );

        // ── Summary ───────────────────────────────────────────────────────
        $this->newLine();
        $this->line('  <fg=yellow>── Files to be generated ─────────────────────────────────────</>');
        $this->displayFileSummary($prefix, $folder, $requestName, $responses, $outputs, $repositories);

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
        $this->displayBindings($prefix, $folder, $responses, $repositories);

        // ── Route hint ────────────────────────────────────────────────────
        $this->newLine();
        $this->line('  <fg=yellow>── Add to routes/api.php ─────────────────────────────────────</>');
        $endpoint = strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $prefix));
        $actionClass = "App\\Http\\Controllers\\Api\\V1\\{$folder}\\{$prefix}Action";
        $this->line("  <fg=gray>Route::get('/{$endpoint}', {$actionClass}::class);</>");

        $this->newLine();
        $this->info('  Done! All files generated successfully.');
        $this->newLine();

        return self::SUCCESS;
    }

    // ──────────────────────────────────────────────────────────────────────
    // Helpers
    // ──────────────────────────────────────────────────────────────────────

    private function gatherSingleName(string $label, string $default): string
    {
        $answer = $this->ask("{$label} <fg=gray>[default: {$default}]</>");
        return empty($answer) ? $default : $answer;
    }

    private function gatherMultipleNames(string $label, string $default, string $singularKey): array
    {
        $hasCustom = $this->confirm("{$label} — use a custom or multiple names?", false);

        if (! $hasCustom) {
            return [$default];
        }

        $names = [];

        while (true) {
            $name = $this->ask("  Enter {$singularKey} name <fg=gray>(leave blank to finish)</>");

            if (empty($name)) {
                break;
            }

            $names[] = $name;
            $this->line("  <fg=green>  Added:</> {$name}");
        }

        return empty($names) ? [$default] : $names;
    }

    private function displayFileSummary(
        string $prefix,
        string $folder,
        string $requestName,
        array $responses,
        array $outputs,
        array $repositories
    ): void {
        $files = [
            "app/Http/Requests/Api/V1/{$folder}/{$requestName}.php",
            "app/Http/Controllers/Api/V1/{$folder}/{$prefix}Action.php",
            "app/UseCases/{$folder}/I{$prefix}UseCase.php",
            "app/UseCases/{$folder}/{$prefix}UseCase.php",
            "app/Domain/{$folder}/Services/I{$prefix}Service.php",
            "app/Infra/{$folder}/Services/{$prefix}Service.php",
        ];

        foreach ($repositories as $repo) {
            $files[] = "app/Domain/{$folder}/Repositories/I{$repo}.php";
            $files[] = "app/Infra/{$folder}/Repositories/{$repo}.php";
        }

        foreach ($outputs as $output) {
            $files[] = "app/Domain/{$folder}/Services/Output/{$output}.php";
        }

        foreach ($responses as $response) {
            $files[] = "app/Http/Responses/Api/V1/{$folder}/I{$response}.php";
            $files[] = "app/Http/Responses/Api/V1/{$folder}/{$response}.php";
        }

        foreach ($files as $file) {
            $this->line("  <fg=gray>  • {$file}</>");
        }

        $this->newLine();
    }

    private function displayBindings(
        string $prefix,
        string $folder,
        array $responses,
        array $repositories
    ): void {
        $ns = "App";

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

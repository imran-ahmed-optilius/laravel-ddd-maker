<?php

declare(strict_types=1);

namespace ImranAhmedOptilius\DddMaker\Tests\Feature;

use ImranAhmedOptilius\DddMaker\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;

class MakeFeatureCommandTest extends TestCase
{
    private Filesystem $files;

    protected function setUp(): void
    {
        parent::setUp();
        $this->files = new Filesystem();
    }

    protected function tearDown(): void
    {
        // Clean up generated files after each test
        $paths = [
            base_path('app/Http/Requests/Api/V1/Test'),
            base_path('app/Http/Controllers/Api/V1/Test'),
            base_path('app/UseCases/Test'),
            base_path('app/Domain/Test'),
            base_path('app/Infra/Test'),
            base_path('app/Http/Responses/Api/V1/Test'),
        ];

        foreach ($paths as $path) {
            if ($this->files->exists($path)) {
                $this->files->deleteDirectory($path);
            }
        }

        parent::tearDown();
    }

    /** @test */
    public function it_registers_the_make_feature_command(): void
    {
        $this->assertArrayHasKey(
            'make:feature',
            $this->app->make(\Illuminate\Contracts\Console\Kernel::class)->all()
        );
    }

    /** @test */
    public function it_generates_all_files_with_default_names(): void
    {
        $this->artisan('make:feature', [
            '--prefix' => 'ForTest',
            '--folder' => 'Test',
        ])->expectsConfirmation('Generate all files now?', 'yes')
          ->expectsConfirmation("  Response(s) — use a custom or multiple names?", 'no')
          ->expectsConfirmation("  Output DTO(s) — use a custom or multiple names?", 'no')
          ->expectsConfirmation("  Repository(ies) — use a custom or multiple names?", 'no')
          ->assertExitCode(0);

        $this->assertFileExists(base_path('app/Http/Requests/Api/V1/Test/ForTestRequest.php'));
        $this->assertFileExists(base_path('app/Http/Controllers/Api/V1/Test/ForTestAction.php'));
        $this->assertFileExists(base_path('app/UseCases/Test/IForTestUseCase.php'));
        $this->assertFileExists(base_path('app/UseCases/Test/ForTestUseCase.php'));
        $this->assertFileExists(base_path('app/Domain/Test/Services/IForTestService.php'));
        $this->assertFileExists(base_path('app/Infra/Test/Services/ForTestService.php'));
        $this->assertFileExists(base_path('app/Domain/Test/Repositories/IForTestRepository.php'));
        $this->assertFileExists(base_path('app/Infra/Test/Repositories/ForTestRepository.php'));
        $this->assertFileExists(base_path('app/Domain/Test/Services/Output/ForTestOutput.php'));
        $this->assertFileExists(base_path('app/Http/Responses/Api/V1/Test/IForTestResponse.php'));
        $this->assertFileExists(base_path('app/Http/Responses/Api/V1/Test/ForTestResponse.php'));
    }

    /** @test */
    public function it_fails_when_prefix_is_empty(): void
    {
        $this->artisan('make:feature', [
            '--prefix' => '',
            '--folder' => 'Test',
        ])->assertExitCode(1);
    }

    /** @test */
    public function it_fails_when_folder_is_empty(): void
    {
        $this->artisan('make:feature', [
            '--prefix' => 'ForTest',
            '--folder' => '',
        ])->assertExitCode(1);
    }
}

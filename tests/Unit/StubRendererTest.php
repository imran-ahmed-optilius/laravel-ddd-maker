<?php

declare(strict_types=1);

namespace ImranAhmedOptilius\DddMaker\Tests\Unit;

use ImranAhmedOptilius\DddMaker\Generators\StubRenderer;
use ImranAhmedOptilius\DddMaker\Tests\TestCase;
use RuntimeException;

class StubRendererTest extends TestCase
{
    private StubRenderer $renderer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->renderer = new StubRenderer();
    }

    /** @test */
    public function it_renders_a_stub_with_variables(): void
    {
        $output = $this->renderer->render('request', [
            'folder'      => 'HomePage',
            'requestName' => 'ForHomePageGetRequest',
        ]);

        $this->assertStringContainsString('namespace App\Http\Requests\Api\V1\HomePage;', $output);
        $this->assertStringContainsString('class ForHomePageGetRequest extends FormRequest', $output);
    }

    /** @test */
    public function it_throws_when_stub_does_not_exist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->renderer->render('non-existent-stub', []);
    }
}

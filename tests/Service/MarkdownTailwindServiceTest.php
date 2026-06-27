<?php

namespace App\Tests\Service;

use App\Service\MapService;
use App\Service\CodeService;
use App\Service\ThemeService;
use App\Service\ButtonService;
use App\Component\MapComponent;
use PHPUnit\Framework\TestCase;
use App\Component\CodeComponent;
use App\Component\ButtonComponent;
use App\Service\ComponentRegistry;
use App\Service\MarkdownTailwindService;
use PHPUnit\Framework\MockObject\MockObject;

class MarkdownTailwindServiceTest extends TestCase
{
    private MarkdownTailwindService $service;
    private ThemeService|MockObject $themeService;
    private ComponentRegistry $componentRegistry;
    private MapService $mapService;
    private MapComponent $mapComponent;
    private ButtonService $buttonService;
    private ButtonComponent $buttonComponent;
    private CodeService $codeService;
    private CodeComponent $codeComponent;


    protected function setUp(): void
    {
        $this->themeService = $this->getMockBuilder(ThemeService::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->themeService->expects($this->any())
            ->method('getCurrentTheme')
            ->willReturn([
                'content' => '',
                'container' => '',
                'link' => '',
                'code' => 'bg-gray-800 text-gray-200',
                'pre' => 'bg-gray-800',
                'title' => '',
                'table' => '',
                'thead' => '',
                'th' => '',
                'td' => '',
                'blockquote' => '',
                'error' => 'text-red-500',
                'button' => 'bg-gray-800 text-gray-200',
                'button_primary' => 'bg-blue-600 text-white',
                'button_big' => 'text-xl py-4 px-8',
            ]);

        $this->mapService = new MapService();
        $this->mapComponent = new MapComponent($this->mapService);

        $this->buttonService = new ButtonService();
        $this->buttonComponent = new ButtonComponent($this->buttonService);

        $this->codeService = new CodeService();
        $this->codeComponent = new CodeComponent($this->codeService);

        $this->componentRegistry = new ComponentRegistry();
        $this->componentRegistry->addComponent($this->mapComponent);
        $this->componentRegistry->addComponent($this->buttonComponent);
        $this->componentRegistry->addComponent($this->codeComponent);

        $this->service = new MarkdownTailwindService($this->themeService, $this->componentRegistry);
    }

    public function testSingleMapInMarkdown(): void
    {
        $markdown = <<<MARKDOWN
# Test

[MAP]
{
  "center": {"lat": 48.8566, "lng": 2.3522},
  "zoom": 14,
  "height": "300px",
  "width": "50%",
  "markers": [
    {"lat": 48.8566, "lng": 2.3522, "popup": "Tour Eiffel"}
  ]
}
[/MAP]
MARKDOWN;

        $html = $this->service->convert($markdown);

        // Check map container
        $this->assertStringContainsString('style="height: 300px; width: 50%;"', $html);
        // Check map initialization
        $this->assertStringContainsString('L.map', $html);
        $this->assertMatchesRegularExpression('/setView\(\[48\.8566\d*, 2\.3522\d*\], 14\)/', $html);
        $this->assertStringContainsString('Tour Eiffel', $html);
    }

    public function testMultipleMapsInMarkdown(): void
    {
        $markdown = <<<MARKDOWN
# Test

[MAP]
{
  "center": {"lat": 48.8566, "lng": 2.3522},
  "zoom": 14
}
[/MAP]

Some content between maps

[MAP]
{
  "center": {"lat": 45.5, "lng": -73.5},
  "zoom": 15
}
[/MAP]
MARKDOWN;

        $html = $this->service->convert($markdown);

        // Check that both maps are present with unique IDs
        $this->assertEquals(2, substr_count($html, 'L.map('));
        $this->assertMatchesRegularExpression('/setView\(\[48\.8566\d*, 2\.3522\d*\], 14\)/', $html);
        $this->assertMatchesRegularExpression('/setView\(\[45\.5\d*, -73\.5\d*\], 15\)/', $html);
        $this->assertStringContainsString('Some content between maps', $html);
    }

    public function testInvalidMapJsonInMarkdown(): void
    {
        $markdown = <<<MARKDOWN
# Test

[MAP]
{
  invalid json here
}
[/MAP]
MARKDOWN;

        $html = $this->service->convert($markdown);
        $this->assertStringContainsString('Error: Invalid map configuration', $html);
    }

    public function testButtonComponent(): void
    {
        $markdown = <<<MARKDOWN
[BUTTON]
{
    "text": "Click me",
    "link": "/test",
    "type": "primary",
    "display": "center"
}
[/BUTTON]
MARKDOWN;

        $html = $this->service->convert($markdown);

        // Check button structure and classes
        $this->assertStringContainsString('flex justify-center', $html);
        $this->assertStringContainsString('bg-blue-600 text-white', $html);
        $this->assertStringContainsString('href="/test"', $html);
        $this->assertStringContainsString('>Click me</a>', $html);
    }

    public function testButtonComponentWithBigType(): void
    {
        $markdown = <<<MARKDOWN
[BUTTON]
{
    "text": "Big Button",
    "link": "/big",
    "type": "big",
    "display": "right"
}
[/BUTTON]
MARKDOWN;

        $html = $this->service->convert($markdown);

        // Check big button specific classes
        $this->assertStringContainsString('flex justify-end', $html);
        $this->assertStringContainsString('text-xl py-4 px-8', $html);
        $this->assertStringContainsString('href="/big"', $html);
        $this->assertStringContainsString('>Big Button</a>', $html);
    }

    public function testInvalidButtonComponent(): void
    {
        $markdown = <<<MARKDOWN
[BUTTON]
invalid json
[/BUTTON]
MARKDOWN;

        $html = $this->service->convert($markdown);
        $this->assertStringContainsString('Error: Invalid button configuration', $html);
    }

    public function testCodeComponent(): void
    {
        $markdown = <<<MARKDOWN
[CODE]
{
    "text": "console.log('Hello World');",
    "display": "center"
}
[/CODE]
MARKDOWN;

        $html = $this->service->convert($markdown);

        // Check code block structure and classes
        $this->assertStringContainsString('flex justify-center', $html);
        $this->assertStringContainsString('bg-gray-800 text-gray-200', $html);
        $this->assertStringContainsString('console.log(&#039;Hello World&#039;);', $html);
    }

    public function testCodeComponentWithLeftAlignment(): void
    {
        $markdown = <<<MARKDOWN
[CODE]
{
    "text": "composer install",
    "display": "left"
}
[/CODE]
MARKDOWN;

        $html = $this->service->convert($markdown);

        // Check left alignment
        $this->assertStringContainsString('flex justify-start', $html);
        $this->assertStringContainsString('composer install', $html);
    }

    public function testInvalidCodeComponent(): void
    {
        $markdown = <<<MARKDOWN
[CODE]
invalid json
[/CODE]
MARKDOWN;

        $html = $this->service->convert($markdown);
        $this->assertStringContainsString('Error: Invalid code configuration', $html);
    }
}

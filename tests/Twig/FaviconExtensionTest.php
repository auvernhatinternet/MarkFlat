<?php

namespace App\Tests\Twig;

use App\Service\ConfigService;
use App\Twig\FaviconExtension;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;

class FaviconExtensionTest extends TestCase
{
    private FaviconExtension $extension;
    private ConfigService|MockObject $configService;

    protected function setUp(): void
    {
        $this->configService = $this->createMock(ConfigService::class);
        $this->extension = new FaviconExtension($this->configService);
    }

    public function testGetFunctionsReturnsFaviconUrlFunction(): void
    {
        $functions = $this->extension->getFunctions();

        $this->assertCount(1, $functions);
        $this->assertSame('favicon_url', $functions[0]->getName());
    }

    public function testGetFaviconUrlReturnsUrlWhenConfigured(): void
    {
        $this->configService->expects($this->once())
            ->method('get')
            ->with('favicon')
            ->willReturn('assets/images/favicon.ico');

        $result = $this->extension->getFaviconUrl();

        $this->assertSame('assets/images/favicon.ico', $result);
    }

    public function testGetFaviconUrlReturnsNullWhenNotConfigured(): void
    {
        $this->configService->expects($this->once())
            ->method('get')
            ->with('favicon')
            ->willReturn(null);

        $result = $this->extension->getFaviconUrl();

        $this->assertNull($result);
    }

    public function testGetFaviconUrlReturnsNullWhenEmpty(): void
    {
        $this->configService->expects($this->once())
            ->method('get')
            ->with('favicon')
            ->willReturn('');

        $result = $this->extension->getFaviconUrl();

        $this->assertNull($result);
    }
}

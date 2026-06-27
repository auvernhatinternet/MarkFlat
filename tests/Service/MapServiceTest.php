<?php

namespace App\Tests\Service;

use App\Service\MapService;
use PHPUnit\Framework\TestCase;

class MapServiceTest extends TestCase
{
    private MapService $mapService;

    protected function setUp(): void
    {
        $this->mapService = new MapService();
    }

    public function testDefaultMapConfiguration(): void
    {
        $result = $this->mapService->getMapConfig([]);

        $this->assertArrayHasKey('html', $result);
        $this->assertArrayHasKey('js', $result);

        // Check HTML contains required attributes
        $this->assertStringContainsString('style="height: 400px; width: 100%;"', $result['html']);

        // Check JavaScript initialization
        $this->assertStringContainsString('L.map', $result['js']);
        $this->assertMatchesRegularExpression('/setView\(\[48\.8566\d*, 2\.3522\d*\], 13\)/', $result['js']);
    }

    public function testCustomMapConfiguration(): void
    {
        $config = [
            'height' => '300px',
            'width' => '50%',
            'center' => ['lat' => 45.5, 'lng' => -73.5],
            'zoom' => 15,
            'markers' => [
                [
                    'lat' => 45.5,
                    'lng' => -73.5,
                    'popup' => 'Test Location',
                ],
            ],
        ];

        $result = $this->mapService->getMapConfig($config);

        // Check custom dimensions
        $this->assertStringContainsString('style="height: 300px; width: 50%;"', $result['html']);

        // Check custom center and zoom
        $this->assertMatchesRegularExpression('/setView\(\[45\.5\d*, -73\.5\d*\], 15\)/', $result['js']);

        // Check marker
        $this->assertMatchesRegularExpression('/L\.marker\(\[45\.5\d*, -73\.5\d*\]\)/', $result['js']);
        $this->assertStringContainsString('Test Location', $result['js']);
    }

    public function testUniqueMapIds(): void
    {
        // Generate multiple maps and ensure IDs are unique
        $map1 = $this->mapService->getMapConfig([]);
        $map2 = $this->mapService->getMapConfig([]);

        preg_match('/id="(map_[^"]+)"/', $map1['html'], $matches1);
        preg_match('/id="(map_[^"]+)"/', $map2['html'], $matches2);

        // Ensure the matches were found before asserting non-empty values
        $this->assertNotEmpty($matches1, 'No match found for map1 ID');
        $this->assertNotEmpty($matches2, 'No match found for map2 ID');

        // Now safely access the matched ID
        $this->assertNotEquals($matches1[1] ?? '', $matches2[1] ?? '');
    }

    public function testEscapedPopupContent(): void
    {
        $config = [
            'markers' => [
                [
                    'lat' => 48.8566,
                    'lng' => 2.3522,
                    'popup' => '<script>alert("XSS")</script>',
                ],
            ],
        ];

        $result = $this->mapService->getMapConfig($config);

        // Check that HTML is properly escaped
        $this->assertStringContainsString(htmlspecialchars('<script>alert("XSS")</script>', ENT_QUOTES), $result['js']);
        $this->assertStringNotContainsString('<script>alert("XSS")</script>', $result['js']);
    }
}

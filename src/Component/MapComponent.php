<?php

namespace App\Component;

use App\Service\MapService;

class MapComponent implements MarkdownComponentInterface
{
    private MapService $mapService;

    public function __construct(MapService $mapService)
    {
        $this->mapService = $mapService;
    }

    public function getPattern(): string
    {
        return '/\[MAP\]\s*\n(.*?)\n\[\/MAP\]/s';
    }

    /**
     * @param string $content
     * @param array<string, string> $theme
     */
    public function process(string $content, array $theme): array
    {
        $config = json_decode(trim($content), true);
        if (!$config) {
            return [
                'html' => '<div class="' . ($theme['error'] ?? 'text-red-500') . '">Error: Invalid map configuration</div>',
                'js' => '',
            ];
        }

        return $this->mapService->getMapConfig($config);
    }

    public function getName(): string
    {
        return 'map';
    }
}

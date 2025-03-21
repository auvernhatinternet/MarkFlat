<?php

namespace App\Component;

use App\Service\MapService;
use App\Service\TwigService;

class MapComponent implements MarkdownComponentInterface
{
    private MapService $mapService;
    private TwigService $twigService;

    public function __construct(
        MapService $mapService,
        TwigService $twigService
    ) {
        $this->mapService = $mapService;
        $this->twigService = $twigService;
    }

    public function getPattern(): string
    {
        return '/\[MAP\]\s*\n(.*?)\n\[\/MAP\]/s';
    }

    /**
     * @param string $content
     * @param array<string, string> $theme
     * @return array{html: string, js: string}
     */
    public function process(string $content, array $theme): array
    {
        $config = json_decode(trim($content), true);
        if (!$config) {
            return [
                'html' => '<div class="' . ($theme['error'] ?? 'text-red-500') . '">Error: Invalid map configuration</div>',
                'js' => ''
            ];
        }

        $mapConfig = $this->mapService->getMapConfig($config);

        return [
            'html' => $this->twigService->render('components/map.html.twig', $mapConfig),
            'js' => $mapConfig['js']
        ];
    }

    public function getName(): string
    {
        return 'map';
    }
}

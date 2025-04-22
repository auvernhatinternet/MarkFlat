<?php

namespace App\Component;

use App\Service\ButtonService;
use App\Service\TwigService;

class ButtonComponent implements MarkdownComponentInterface
{
    private ButtonService $buttonService;
    private TwigService $twigService;

    public function __construct(
        ButtonService $buttonService,
        TwigService $twigService
    ) {
        $this->buttonService = $buttonService;
        $this->twigService = $twigService;
    }

    public function getPattern(): string
    {
        return '/\[BUTTON\]\s*\n(.*?)\n\[\/BUTTON\]/s';
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
                'html' => '<div class="' . ($theme['error'] ?? 'text-red-500') . '">Error: Invalid button configuration</div>',
                'js' => ''
            ];
        }

        $buttonConfig = $this->buttonService->getButtonConfig($config, $theme);

        return [
            'html' => $this->twigService->render('components/button.html.twig', $buttonConfig),
            'js' => $buttonConfig['js']
        ];
    }

    public function getName(): string
    {
        return 'button';
    }
}

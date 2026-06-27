<?php

namespace App\Component;

use App\Service\ButtonService;

class ButtonComponent implements MarkdownComponentInterface
{
    private ButtonService $buttonService;

    public function __construct(ButtonService $buttonService)
    {
        $this->buttonService = $buttonService;
    }

    public function getPattern(): string
    {
        return '/\[BUTTON\]\s*\n(.*?)\n\[\/BUTTON\]/s';
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
                'html' => '<div class="' . ($theme['error'] ?? 'text-red-500') . '">Error: Invalid button configuration</div>',
                'js' => '',
            ];
        }

        return $this->buttonService->getButtonConfig($config, $theme);
    }

    public function getName(): string
    {
        return 'button';
    }
}

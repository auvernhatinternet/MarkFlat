<?php

namespace App\Component;

use App\Service\AccordionService;
use App\Service\TwigService;

class AccordionComponent implements MarkdownComponentInterface
{
    private AccordionService $accordionService;
    private TwigService $twigService;

    public function __construct(
        AccordionService $accordionService,
        TwigService $twigService
    ) {
        $this->accordionService = $accordionService;
        $this->twigService = $twigService;
    }

    public function getPattern(): string
    {
        return '/\[ACCORDION\]\s*\n(.*?)\n\[\/ACCORDION\]/s';
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

        $accordionConfig = $this->accordionService->getAccordionConfig($config, $theme);

        return [
            'html' => $this->twigService->render('components/accordion.html.twig', $accordionConfig),
            'js' => $accordionConfig['js']
        ];
    }

    public function getName(): string
    {
        return 'button';
    }
}

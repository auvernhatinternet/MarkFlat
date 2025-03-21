<?php

namespace App\Component;

use App\Service\CodeService;
use App\Service\TwigService;

class CodeComponent implements MarkdownComponentInterface
{
    private CodeService $codeService;
    private TwigService $twigService;

    public function __construct(
        CodeService $codeService,
        TwigService $twigService
    ) {
        $this->codeService = $codeService;
        $this->twigService = $twigService;
    }

    public function getPattern(): string
    {
        return '/\[CODE\]\s*\n(.*?)\n\[\/CODE\]/s';
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
                'html' => '<div class="' . ($theme['error'] ?? 'text-red-500') . '">Error: Invalid code configuration</div>',
                'js' => ''
            ];
        }

        $codeConfig = $this->codeService->getCodeConfig($config, $theme);

        return [
            'html' => $this->twigService->render('components/code.html.twig', $codeConfig),
            'js' => $codeConfig['js']
        ];
    }

    public function getName(): string
    {
        return 'code';
    }
}

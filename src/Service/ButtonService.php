<?php

namespace App\Service;

class ButtonService
{
    /**
     * List of Tailwind spacing classes to extract
     */
    private const SPACING_PATTERNS = [
        '/\s*(m[trblxy]?-\d+)/'  // mt-4, mr-2, mb-3, ml-1, mx-4, my-2, m-4
    ];

    /**
     * @param array{
     *  text?: string,
     *  link?: string,
     *  type?: string,
     *  display?: string
     * } $config
     * @param array<string, string> $theme
     * @return array{buttonClasses: string, spacingClasses: string, display: string, link: string, text: string, js: string}
     */
    public function getButtonConfig(array $config, array $theme = []): array
    {
        $defaults = [
            'text' => 'Button',
            'link' => '#',
            'type' => 'primary',
            'display' => 'left'
        ];

        $config = array_merge($defaults, $config);

        $buttonClasses = match($config['type']) {
            'primary' => $theme['button_primary'] ?? $theme['button'],
            'secondary' => $theme['button_secondary'] ?? $theme['button'],
            'outline' => $theme['button_outline'] ?? $theme['button'],
            'big' => $theme['button_big'] ?? $theme['button'],
            default => $theme['button_default'] ?? $theme['button']
        };

        // Extract spacing classes
        $spacingClasses = '';
        foreach (self::SPACING_PATTERNS as $pattern) {
            if (preg_match_all($pattern, $buttonClasses, $matches)) {
                foreach ($matches[1] as $match) {
                    $spacingClasses .= ' ' . $match;
                    $buttonClasses = str_replace($match, '', $buttonClasses);
                }
            }
        }

        $buttonClasses = trim($buttonClasses);
        $spacingClasses = trim($spacingClasses);

        return [
            'buttonClasses' => $buttonClasses,
            'spacingClasses' => $spacingClasses,
            'display' => $config['display'],
            'link' => $config['link'],
            'text' => $config['text'],
            'js' => ''
        ];
    }
}

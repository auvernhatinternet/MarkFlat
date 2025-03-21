<?php

namespace App\Service;

class AccordionService
{
    /**
     * @param array{
     *  text?: string,
     *  display?: string
     * } $config
     * @param array<string, string> $theme
     * @return array{accordionClasses: string, display: string, text: string, js: string}
     */
    public function getAccordionConfig(array $config, array $theme = []): array
    {
        $defaults = [
            'text' => '',
            'display' => 'left'
        ];

        $config = array_merge($defaults, $config);

        return [
            'text' => $config['text'],
            'display' => $config['display'],
            'accordionClasses' => $theme['accordion'] ?? 'bg-gray-800 text-gray-200 rounded p-4',
            'js' => ''
        ];
    }
}

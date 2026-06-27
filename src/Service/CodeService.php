<?php

namespace App\Service;

class CodeService
{
    /**
     * @param array{
     *  text?: string,
     *  display?: string
     * } $config
     * @param array<string, string> $theme
     * @return array{html: string, js: string}
     */
    public function getCodeConfig(array $config, array $theme = []): array
    {
        $defaults = [
            'text' => '',
            'display' => 'left',
        ];

        $config = array_merge($defaults, $config);

        // Add display alignment classes
        $displayClasses = match($config['display']) {
            'center' => 'flex justify-center',
            'right' => 'flex justify-end',
            default => 'flex justify-start'
        };

        // Create code block HTML with wrapper div for alignment
        $html = sprintf(
            '<div class="%s"><code class="%s px-4 py-1 rounded">%s</code></div>',
            $displayClasses,
            $theme['code'] ?? 'bg-gray-800 text-gray-200 rounded p-4',
            htmlspecialchars($config['text'])
        );

        return [
            'html' => $html,
            'js' => '',
        ];
    }
}

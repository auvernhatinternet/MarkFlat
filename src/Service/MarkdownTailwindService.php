<?php

namespace App\Service;

use League\CommonMark\CommonMarkConverter;

class MarkdownTailwindService
{
    private const HEADING_STYLES = [
        'h1' => 'text-4xl font-bold mb-4',
        'h2' => 'text-3xl font-bold mb-3',
        'h3' => 'text-2xl font-bold mb-2',
        'h4' => 'text-xl font-bold mb-2',
        'h5' => 'text-lg font-bold mb-2',
        'h6' => 'font-bold mb-2',
    ];

    private const LIST_STYLES = [
        'ul' => 'list-disc list-inside mb-4',
        'ol' => 'list-decimal list-inside mb-4',
        'li' => 'mb-1',
    ];

    private const TEXT_STYLES = [
        'p' => 'mb-4',
        'strong' => 'font-bold',
        'em' => 'italic',
        'blockquote' => 'p-4 my-4 border-s-4 italic',
        'hr' => 'border-t p-1 my-6',
    ];

    private const CODE_STYLES = [
        'code' => 'p-1 rounded',
        'pre' => 'rounded overflow-auto',
        'pre-code' => 'p-2 rounded block w-full',
    ];

    private const TABLE_STYLES = [
        'table' => 'min-w-full shadow rounded',
        'th' => 'py-2 px-4 border-l-1 first:border-l-0',
        'td' => 'py-2 px-4 border-t-1 border-l-1 first:border-l-0',
    ];

    private const COMPONENT_PLACEHOLDER = '___COMPONENT_%d___';

    /**
     * @var array<string, string>
     */
    private array $componentPlaceholders = [];

    private ThemeService $themeService;
    private ComponentRegistry $componentRegistry;
    private CommonMarkConverter $converter;

    public function __construct(
        ThemeService $themeService,
        ComponentRegistry $componentRegistry
    ) {
        $this->converter = new CommonMarkConverter();
        $this->themeService = $themeService;
        $this->componentRegistry = $componentRegistry;
    }

    private function getBaseUrl(): string
    {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $baseDir = dirname($_SERVER['SCRIPT_NAME']);
        return rtrim($protocol . $host . $baseDir, '/');
    }

    public function convert(string $content): string
    {
        $baseUrl = $this->getBaseUrl();
        $theme = $this->themeService->getCurrentTheme();

        // Process code blocks first to prevent component syntax inside code blocks from being processed
        $content = $this->processCodeBlocks($content);

        // Process components
        [$content, $scripts] = $this->processComponents($content, $theme);

        // Convert markdown to HTML
        $html = (string) $this->converter->convert($content);

        // Apply Tailwind classes
        $html = $this->applyTailwindClasses($html, $theme, $baseUrl);

        // Append component scripts if any
        if (!empty($scripts)) {
            $html .= $this->wrapScripts($scripts);
        }

        return $html;
    }

    private function processCodeBlocks(string $content): string
    {
        return preg_replace_callback('/```.*?\n(.*?)```/s', function ($matches) {
            return $matches[0];
        }, $content);
    }

    /**
     * Process markdown components in content
     * @param string $content The content to process
     * @param array<string, string> $theme The theme configuration
     * @return array{0: string, 1: string[]} Array containing processed content and scripts
     */
    private function processComponents(string $content, array $theme): array
    {
        $scripts = [];
        foreach ($this->componentRegistry->getComponents() as $component) {
            $content = preg_replace_callback($component->getPattern(), function ($matches) use ($component, $theme, &$scripts) {
                $result = $component->process($matches[1], $theme);
                if ($result['js']) {
                    $scripts[] = $result['js'];
                }
                return $result['html'];
            }, $content);
        }
        return [$content, $scripts];
    }

    /**
     * Wrap component scripts in a DOMContentLoaded event listener
     * @param string[] $scripts Array of JavaScript code strings
     */
    private function wrapScripts(array $scripts): string
    {
        return sprintf(
            "\n<script>document.addEventListener('DOMContentLoaded', function() {\n%s\n});</script>",
            implode("\n", $scripts)
        );
    }

    /**
     * Apply Tailwind classes to HTML elements
     * @param string $html The HTML content
     * @param array<string, string> $theme The theme configuration
     * @param string $baseUrl The base URL for asset paths
     */
    private function applyTailwindClasses(string $html, array $theme, string $baseUrl): string
    {
        // First, protect component content
        $this->componentPlaceholders = [];
        $html = $this->protectComponents($html);

        // Apply heading styles
        foreach (self::HEADING_STYLES as $tag => $styles) {
            $html = str_replace(
                "<$tag>",
                "<$tag class=\"{$theme['content']} $styles\">",
                $html
            );
        }

        // Apply list styles
        foreach (self::LIST_STYLES as $tag => $styles) {
            $html = str_replace(
                "<$tag>",
                "<$tag class=\"" . ($tag !== 'li' ? $theme['content'] . ' ' : '') . "$styles\">",
                $html
            );
        }

        // Apply text styles
        foreach (self::TEXT_STYLES as $tag => $styles) {
            $themeKey = $tag === 'blockquote' ? 'blockquote' : 'content';
            $html = str_replace(
                "<$tag>",
                "<$tag class=\"{$theme[$themeKey]} $styles\">",
                $html
            );
        }

        // Apply code styles
        $html = $this->applyCodeStyles($html, $theme);

        // Apply table styles
        $html = $this->applyTableStyles($html, $theme);

        // Process regular links
        $html = str_replace('<a ', '<a class="' . $theme['link'] . '" ', $html);

        // Process images with base URL
        $html = $this->processImages($html, $baseUrl);

        // Restore protected components
        $html = $this->restoreComponents($html);

        return $html;
    }

    private function protectComponents(string $html): string
    {
        // Find all component content (anything between component tags that has a class attribute)
        $pattern = '/<([a-z]+)\s+class="[^"]+"\s*[^>]*>.*?<\/\1>/s';

        // Keep protecting components until no more are found (handles nesting)
        $previousHtml = '';
        while ($html !== $previousHtml) {
            $previousHtml = $html;
            $html = preg_replace_callback($pattern, function ($matches) {
                $id = count($this->componentPlaceholders);
                $placeholder = sprintf(self::COMPONENT_PLACEHOLDER, $id);
                $this->componentPlaceholders[$placeholder] = $matches[0];
                return $placeholder;
            }, $html);
        }

        return $html;
    }

    private function restoreComponents(string $html): string
    {
        // Restore components from most deeply nested to least nested
        $placeholders = array_reverse(array_keys($this->componentPlaceholders));
        foreach ($placeholders as $placeholder) {
            $html = str_replace($placeholder, $this->componentPlaceholders[$placeholder], $html);
        }
        return $html;
    }

    /**
     * Apply code block styles
     * @param string $html The HTML content
     * @param array<string, string> $theme The theme configuration
     */
    private function applyCodeStyles(string $html, array $theme): string
    {
        // Apply inline code styles
        $html = str_replace(
            '<code>',
            '<code class="' . $theme['code'] . ' ' . self::CODE_STYLES['code'] . '">',
            $html
        );

        // Apply pre styles
        $html = str_replace(
            '<pre>',
            '<pre class="' . $theme['pre'] . ' ' . self::CODE_STYLES['pre'] . '">',
            $html
        );

        // Apply special styles for code blocks
        return preg_replace_callback(
            '/<pre[^>]*>(.*?)<\/pre>/s',
            function ($match) use ($theme) {
                $content = preg_replace(
                    '/<code([^>]*)>/',
                    '<code class="' . $theme['code'] . ' ' . self::CODE_STYLES['pre-code'] . '"$1>',
                    $match[1]
                );
                return '<pre class="' . $theme['pre'] . ' rounded shadow overflow-auto mt-2 mb-2">' . $content . '</pre>';
            },
            $html
        );
    }

    /**
     * Apply table styles
     * @param string $html The HTML content
     * @param array<string, string> $theme The theme configuration
     */
    private function applyTableStyles(string $html, array $theme): string
    {
        $html = str_replace(
            '<table>',
            '<table class="' . $theme['table'] . ' ' . self::TABLE_STYLES['table'] . '">',
            $html
        );

        $html = str_replace('<thead>', '<thead class="' . $theme['thead'] . '">', $html);

        $html = str_replace(
            '<th>',
            '<th class="' . $theme['th'] . ' ' . self::TABLE_STYLES['th'] . '">',
            $html
        );

        $html = str_replace(
            '<td>',
            '<td class="' . $theme['td'] . ' ' . self::TABLE_STYLES['td'] . '">',
            $html
        );

        return $html;
    }

    private function processImages(string $html, string $baseUrl): string
    {
        return preg_replace_callback(
            '/<img(.*?)src="([^"]*)"([^>]*)>/',
            function ($matches) use ($baseUrl) {
                $src = $matches[2];
                if (preg_match('/^\/[^\/]/', $src) && !preg_match('/^(http|https|www)/', $src)) {
                    $src = $baseUrl . $src;
                }
                return '<img' . $matches[1] . 'src="' . $src . '"' . $matches[3] . ' class="rounded mx-auto d-block">';
            },
            $html
        );
    }
}

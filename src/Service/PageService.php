<?php

namespace App\Service;

use Symfony\Component\Yaml\Yaml;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class PageService
{
    private string $projectDir;
    private string $regexPageContent = '/^---\n(.*?)\n---\n(.*)/s';

    public function __construct(ParameterBagInterface $params)
    {
        $this->projectDir = $params->get('kernel.project_dir');
    }

    /**
     * @return array<int, array{path: string, title: string, menu_order: int, show_in_menu: bool}>
     */
    public function getAllPages(string $pagesDirectory): array
    {
        $pages = [];
        $pagesDirectory = $this->projectDir . '/' . $pagesDirectory;
        if (!is_dir($pagesDirectory)) {
            return $pages;
        }

        $files = scandir($pagesDirectory);
        foreach ($files as $file) {
            if (str_ends_with($file, '.md')) {
                $metadata = $this->getPageMetadata($pagesDirectory . '/' . $file);
                $pages[] = [
                    'path' => basename($file, '.md'),
                    'title' => $metadata['title'] ?? basename($file, '.md'),
                    'menu_order' => $metadata['menu_order'] ?? 999,
                    'show_in_menu' => $metadata['show_in_menu'] ?? false
                ];
            }
        }

        // Sort pages by menu_order
        usort($pages, function ($a, $b) {
            return $a['menu_order'] <=> $b['menu_order'];
        });

        return $pages;
    }

    /**
     * @return array{content: string, title: string, path: string, menu_order: int, show_in_menu: bool}|null
     */
    public function getPage(string $pagesDirectory, string $path): ?array
    {
        $filePath = $this->projectDir . '/' . $pagesDirectory . '/' . $path . '.md';

        if (!file_exists($filePath)) {
            return null;
        }

        $content = file_get_contents($filePath);
        if ($content === false) {
            return null;
        }

        $metadata = $this->getPageMetadata($filePath);
        $parsedContent = $this->parsePageContent($content);

        return [
            'content' => $parsedContent,
            'title' => $metadata['title'] ?? basename($filePath, '.md'),
            'path' => $path,
            'menu_order' => $metadata['menu_order'] ?? 999,
            'show_in_menu' => $metadata['show_in_menu'] ?? false
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function getPageMetadata(string $filePath): array
    {
        $content = file_get_contents($filePath);
        if ($content === false) {
            return [];
        }

        // Normalize line endings to \n
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        if (preg_match($this->regexPageContent, $content, $matches)) {
            return Yaml::parse($matches[1]) ?? [];
        }

        return [];
    }

    /**
     * Parse the content of a page
     * @param string $content The content of the page
     * @return string The parsed content
     */
    private function parsePageContent(string $content): string
    {
        $content = str_replace(["\r\n", "\r"], "\n", $content);

        if (preg_match($this->regexPageContent, $content, $matches)) {
            return trim($matches[2]);
        }

        return trim($content);
    }
}

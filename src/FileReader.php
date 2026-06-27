<?php

namespace App;

use Symfony\Component\HttpKernel\KernelInterface;
use Twig\Extra\Markdown\MarkdownInterface;

class FileReader
{
    private MarkdownInterface $markdown;
    private string $projectDir;

    public function __construct(KernelInterface $kernel, MarkdownInterface $markdown)
    {
        $this->markdown = $markdown;
        $this->projectDir = $kernel->getProjectDir();
    }

    /**
     * @return array<string, mixed>
    */
    public function readFile(string $filePath): array
    {
        $fullPath = $this->projectDir . '/' . $filePath;

        if (!file_exists($fullPath)) {
            throw new \RuntimeException(sprintf('File "%s" not found.', $filePath));
        }

        $content = file_get_contents($fullPath);
        if ($content === false) {
            throw new \RuntimeException(sprintf('Unable to read file "%s".', $filePath));
        }

        $metadata = [];
        if (preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)/s', $content, $matches)) {
            $yamlContent = $matches[1];
            $content = $matches[2];

            foreach (explode("\n", $yamlContent) as $line) {
                if (preg_match('/^(\w+):\s*(.*)$/', $line, $kv)) {
                    $metadata[$kv[1]] = trim($kv[2]);
                }
            }
        }

        $htmlContent = $this->markdown->convert($content);

        return [
            'metadata' => $metadata,
            'content' => $htmlContent,
            'raw_content' => $content,
            'path' => $filePath,
        ];
    }
}

<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ConfigService
{
    private ParameterBagInterface $params;

    /**
     * @var array<string, mixed>
     */
    private array $config;

    public function __construct(ParameterBagInterface $params)
    {
        $this->params = $params;
        $this->loadConfig();
    }

    private function loadConfig(): void
    {
        $this->config = [
            'site_name' => $_ENV['MF_CMS_SITE_NAME'] ?? 'MarkFlat CMS',
            'posts_per_page' => (int)($_ENV['MF_CMS_POSTS_PER_PAGE'] ?? 10),
            'theme' => $_ENV['MF_CMS_THEME'] ?? 'default',
            'favicon' => $_ENV['MF_CMS_FAVICON'] ?? null,
            'posts_dir' => $_ENV['MF_CMS_POSTS_DIR'] ?? '/posts',
            'pages_dir' => $_ENV['MF_CMS_PAGES_DIR'] ?? '/pages',
        ];
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    public function getProjectDir(): string
    {
        $projectDir = $this->params->get('kernel.project_dir');

        if (is_scalar($projectDir) || is_null($projectDir)) {
            return (string) $projectDir;
        }
        throw new \UnexpectedValueException('Project directory is not a valid type.');
    }

    public function getPostsDir(): string
    {
        return (string)($this->getProjectDir() . $this->get('posts_dir'));
    }

    public function getPagesDir(): string
    {
        return (string)($this->getProjectDir() . $this->get('pages_dir'));
    }
}

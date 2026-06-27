<?php

namespace App\Twig;

use App\Service\ConfigService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class FaviconExtension extends AbstractExtension
{
    private ConfigService $configService;

    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('favicon_url', [$this, 'getFaviconUrl']),
        ];
    }

    public function getFaviconUrl(): ?string
    {
        $favicon = $this->configService->get('favicon');
        return $favicon ?: null;
    }
}

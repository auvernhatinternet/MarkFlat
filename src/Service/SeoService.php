<?php

namespace App\Service;

class SeoService
{
    private $configService;

    public function __construct(ConfigService $configService)
    {
        $this->configService = $configService;
    }

    public function getMetadata(?string $title = null, ?string $description = null): array
    {
        $siteName = $this->configService->get('MF_CMS_SITE_NAME');

        return [
            'title' => $title ? "$title - $siteName" : $siteName,
            'description' => $description ?? $this->configService->get('MF_CMS_SITE_DESCRIPTION', ''),
            'robots' => 'index, follow',
            'og:title' => $title ?? $siteName,
            'og:description' => $description ?? $this->configService->get('MF_CMS_SITE_DESCRIPTION', ''),
            'og:type' => 'website',
            'twitter:card' => 'summary',
        ];
    }

    public function getStructuredData(array $data = []): array
    {
        $baseData = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $this->configService->get('MF_CMS_SITE_NAME'),
            'description' => $this->configService->get('MF_CMS_SITE_DESCRIPTION', ''),
        ];

        return array_merge($baseData, $data);
    }

    public function getBreadcrumbs(array $items): array
    {
        $breadcrumbs = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => [],
        ];

        foreach ($items as $position => $item) {
            $breadcrumbs['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $position + 1,
                'name' => $item['name'],
                'item' => $item['url'],
            ];
        }

        return $breadcrumbs;
    }
}

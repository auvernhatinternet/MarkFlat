<?php

namespace App\Service;

class MapService
{
    private function generateUniqueId(): string
    {
        return 'map_' . time() . '_' . bin2hex(random_bytes(4));
    }

    /**
     * @param array{
     *  id?: string,
     *  height?: string,
     *  width?: string,
     *  center?: array{lat: float, lng: float},
     *  zoom?: int,
     *  markers?: array<array{lat: float, lng: float, popup?: string}>,
     *  tiles?: string
     * } $config
     * @return array{
     *  id: string,
     *  height: string,
     *  width: string,
     *  center: array{lat: float, lng: float},
     *  zoom: int,
     *  tiles: string,
     *  markers: array<array{lat: float, lng: float, popup?: string}>,
     *  js: string
     * }
     */
    public function getMapConfig(array $config): array
    {
        $defaults = [
            'id' => $this->generateUniqueId(),
            'height' => '400px',
            'width' => '100%',
            'center' => ['lat' => 48.8566, 'lng' => 2.3522],
            'zoom' => 13,
            'tiles' => 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png',
            'markers' => []
        ];

        if (!isset($config['id'])) {
            $config['id'] = $defaults['id'];
        }
        $config = array_merge($defaults, $config);

        $js = sprintf(
            'const %s = L.map("%s").setView([%f, %f], %d);',
            $config['id'],
            $config['id'],
            $config['center']['lat'],
            $config['center']['lng'],
            $config['zoom']
        );

        $js .= sprintf(
            'L.tileLayer("%s", { attribution: "© OpenStreetMap contributors" }).addTo(%s);',
            $config['tiles'],
            $config['id']
        );

        foreach ($config['markers'] as $marker) {
            $js .= sprintf(
                'L.marker([%f, %f])',
                $marker['lat'],
                $marker['lng']
            );

            if (isset($marker['popup'])) {
                $js .= sprintf(
                    '.bindPopup("%s")',
                    htmlspecialchars($marker['popup'], ENT_QUOTES)
                );
            }

            $js .= sprintf('.addTo(%s);', $config['id']);
        }

        return array_merge($config, ['js' => $js]);
    }
}

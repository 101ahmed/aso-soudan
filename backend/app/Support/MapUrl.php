<?php

namespace App\Support;

class MapUrl
{
    public static function normalize(?string $mapUrl, ?string $location): ?string
    {
        $query = self::queryFrom($mapUrl, $location);
        if ($query !== '') {
            return 'https://www.openstreetmap.org/search?query='.rawurlencode($query);
        }

        $mapUrl = trim((string) $mapUrl);

        return self::isRealHttpUrl($mapUrl) ? $mapUrl : null;
    }

    public static function queryFrom(?string $mapUrl, ?string $location): string
    {
        $mapUrl = trim((string) $mapUrl);
        $location = trim((string) $location);

        if ($mapUrl !== '' && self::isRealHttpUrl($mapUrl)) {
            $queryString = parse_url($mapUrl, PHP_URL_QUERY);
            if (is_string($queryString) && $queryString !== '') {
                parse_str($queryString, $params);
                foreach (['query', 'q'] as $key) {
                    $value = trim((string) ($params[$key] ?? ''));
                    if ($value !== '') {
                        return $value;
                    }
                }
            }

            return $location;
        }

        if ($mapUrl !== '') {
            $stripped = (string) preg_replace('#^https?://#i', '', $mapUrl);

            return rawurldecode(str_replace('+', ' ', $stripped));
        }

        return $location;
    }

    public static function isRealHttpUrl(string $url): bool
    {
        if ($url === '' || preg_match('#\s#', $url) || ! preg_match('#^https?://#i', $url)) {
            return false;
        }

        $host = parse_url($url, PHP_URL_HOST);
        if (! is_string($host) || ! str_contains($host, '.')) {
            return false;
        }

        if (str_starts_with($host, 'xn--')) {
            return str_contains($host, 'google') || str_contains($host, 'openstreetmap');
        }

        return true;
    }
}

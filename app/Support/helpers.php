<?php

if (! function_exists('site_asset')) {
    function site_asset(string $path = ''): string
    {
        $prefix = trim((string) config('app.asset_prefix', ''), '/');
        $cleanPath = ltrim($path, '/');

        if ($prefix !== '') {
            return asset($prefix . '/' . $cleanPath);
        }

        return asset($cleanPath);
    }
}
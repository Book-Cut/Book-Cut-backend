<?php

namespace App\Support;

class FrontendUrl
{
    public static function to(string $ruta = '/', array $query = []): string
    {
        $crudo = trim((string) config('services.frontend_url', 'https://book-cut.github.io'));


        $crudo = explode('#', $crudo)[0];
        $partes = parse_url($crudo) ?: [];
        $host = (isset($partes['scheme']) ? $partes['scheme'].'://' : 'https://')
            .($partes['host'] ?? 'book-cut.github.io')
            .(isset($partes['port']) ? ':'.$partes['port'] : '');

        $path = trim($partes['path'] ?? '', '/');
        if ($path === '') {
            $path = trim((string) config('services.frontend_path', '/DIST-FRONT'), '/');
        }

        $url = $host.($path !== '' ? '/'.$path : '').'/#/'.ltrim($ruta, '/');

        return $query ? $url.'?'.http_build_query($query) : $url;
    }
}

<?php declare(strict_types=1);

namespace Citrus\Http;

use Citrus\Http\Messages\HttpUri;
use Psr\Http\Message\UriInterface;

class Uri extends HttpUri
{

    /**
     * Create URI instance from SERVER globals array.
     *
     * @return UriInterface
     */
    static public function createFromGlobals(): UriInterface
    {
        $scheme = 'http' . (($_SERVER['HTTPS'] ?? 'off') === 'on'? 's': '');

        $userinfo = !empty($_SERVER['PHP_AUTH_USER'])? $_SERVER['PHP_AUTH_USER']: '';
        if (!empty($userinfo) && !empty($_SERVER['PHP_AUTH_PW'])) {
            $userinfo .= $_SERVER['PHP_AUTH_PW'];
        }

        $path = $_SERVER['REQUEST_URI'];
        $index = strpos($path, '?');
        $query = $index !== false? substr($path, $index+1): '';
        $path = $index !== false? substr($path, 0, $index): $path;
        
        return new self(
            $scheme,
            $userinfo,
            $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'],
            $_SERVER['SERVER_PORT']? intval($_SERVER['SERVER_PORT']): null,
            $path,
            $query
        );
    }

}

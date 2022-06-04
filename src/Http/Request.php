<?php declare(strict_types=1);

namespace Citrus\Http;

use Citrus\Http\Messages\HttpRequest;
use Citrus\Http\Stacks\FileStack;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

class Request extends HttpRequest
{

    /**
     * Create Request instance from Globals.
     *
     * @return static
     */
    static public function createFromGlobals()
    {
        $request = $_SERVER['REQUEST_URI'];
        if (($index = strpos($request, '?')) !== false) {
            $request = substr($request, 0, $index);
        }

        return new self(
            $_SERVER['REQUEST_METHOD'] . ' ' . $request . ' ' . $_SERVER['SERVER_PROTOCOL'],
            getallheaders()
        );
    }

    /**
     * Create a new Request instance.
     *
     * @param string $target
     * @param array $headers
     * @param StreamInterface $body
     * @param UriInterface $uri
     * @param null|array $serverParams
     * @param null|array $cookieParams
     * @param null|array $queryParams
     * @param null|array|FileStack $uploadedFiles
     * @param null|array $attributes
     */
    public function __construct(
        string $target,
        array $headers = [],
        null | StreamInterface $body = null,
        null | UriInterface $uri = null,
        null | array $serverParams = null,
        null | array $cookieParams = null,
        null | array $queryParams = null,
        null | array | FileStack $uploadedFiles = null,
        null | array $attributes = null
    ) {
        if (!($uploadedFiles instanceof FileStack)) {
            $uploadedFiles = new FileStack($uploadedFiles ?? $_FILES);
        }
        $this->files = $uploadedFiles;
        
        parent::__construct(
            $target,
            $headers,
            $body ?? Stream::createFromRequest(),
            $uri ?? Uri::createFromGlobals(),
            $serverParams ?? $_SERVER,
            $cookieParams ?? $_COOKIE,
            $queryParams ?? $_GET,
            $uploadedFiles->toArray(),
            $attributes ?? []
        );
    }

    public function inputs()
    {

    }

    public function input(string $key)
    {

    }

    public function files()
    {

    }
    
    public function file(string $key)
    {

    }

    public function body()
    {

    }

    public function raw()
    {
        
    }

}

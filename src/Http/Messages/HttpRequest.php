<?php declare(strict_types=1);

namespace Citrus\Http\Messages;

use Citrus\Contracts\HttpRequestHeaders;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * @internal Used internally to fulfill PSR-7. Citrus does not implement the 
 * default RequestInterface, instead we directly refer to ServerRequest.
 */
class HttpRequest extends HttpMessage implements HttpRequestHeaders, ServerRequestInterface
{

    /**
     * Get Request Header in the right case-format.
     *
     * @param string $header The desired header name to receive.
     * @param bool $returnInputOnFailure Return the passed header or null on
     *             failure (When the header could not be found).
     * @return string|null
     */
    static public function findHeader(string $header, bool $returnInputOnFailure = true): ?string
    {
        static $requestHeaderCased;
        
        if (empty($requestHeaderCased)) {
            $requestHeaderCased = array_combine(array_map(fn($val) => strtolower($val), self::REQUEST_HEADERS), self::REQUEST_HEADERS);
        }

        return $requestCased[strtolower($header)] ?? ($returnInputOnFailure? $header: null);
    }

    /**
     * Request Method
     *
     * @var string
     */
    protected string $method;

    /**
     * Request Target
     *
     * @var string
     */
    protected string $requestTarget;

    /**
     * Request URI
     *
     * @var UriInterface
     */
    protected UriInterface $uri;

    /**
     * Server Parameters
     *
     * @var array
     */
    protected array $serverParams;

    /**
     * Cookie Parameters
     *
     * @var array
     */
    protected array $cookieParams;

    /**
     * Query Parameters
     *
     * @var array
     */
    protected array $queryParams;

    /**
     * All Uploaded files 
     *
     * @var array
     */
    protected array $uploadedFiles;

    /**
     * Additional Attributes
     *
     * @var array
     */
    protected array $attributes;

    /**
     * Create a new Request instance.
     *
     * @param string $target
     * @param array $headers
     * @param StreamInterface $body
     * @param UriInterface $uri
     * @param array $serverParams
     * @param array $cookieParams
     * @param array $queryParams
     * @param array $uploadedFiles
     * @param array $attributes
     */
    public function __construct(
        string $target,
        array $headers,
        StreamInterface $body,
        UriInterface $uri,
        array $serverParams,
        array $cookieParams,
        array $queryParams,
        array $uploadedFiles,
        array $attributes
    ) {
        [$method, $requestTarget, $protocol] = explode(' ', $target, 3);
        $protocolVersion = substr($protocol, strpos($protocol, '/'));

        parent::__construct($protocolVersion, $headers, $body);

        $this->method = $method;
        $this->requestTarget = $requestTarget;
        $this->uri = $uri;
        $this->serverParams = $serverParams;
        $this->cookieParams = $cookieParams;
        $this->queryParams = $queryParams;
        $this->uploadedFiles = $uploadedFiles;
        $this->attributes = $attributes;
    }

    /**
     * @inheritDoc
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * @inheritDoc
     */
    public function withMethod($method)
    {
        $method = strtoupper($method);
        return $this->clone(['method' => $method]);
    }

    /**
     * @inheritDoc
     */
    public function getRequestTarget()
    {
        return $this->requestTarget;
    }

    /**
     * @inheritDoc
     */
    public function withRequestTarget($requestTarget)
    {
        return $this->clone(['requestTarget' => $requestTarget]);
    }

    /**
     * @inheritDoc
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @inheritDoc
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $headers = $this->headers;
        $headerKeyMap = $this->headerKeyMap;

        if (!$preserveHost && ($host = $uri->getHost()) !== '') {
            $headerKey = $headerKeyMap['HOST'] ?? 'Host';
            $headers[$headerKey] = $host;
        }

        return $this->clone([
            'headers' => $headers,
            'headerKeyMap' => $this->headerKeyMap,
            'uri' => $this->uri
        ]);
    }

    /**
     * @inheritDoc
     */
    public function getServerParams()
    {
        return $this->serverParams;
    }

    /**
     * @inheritDoc
     */
    public function getCookieParams()
    {
        return $this->cookieParams;
    }

    /**
     * @inheritDoc
     */
    public function withCookieParams(array $cookies)
    {
        return $this->clone(['cookieParams' => $cookies]);
    }

    /**
     * @inheritDoc
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * @inheritDoc
     */
    public function withQueryParams(array $query)
    {
        return $this->clone(['queryParams' => $query]);
    }

    /**
     * @inheritDoc
     */
    public function getUploadedFiles()
    {
        return $this->uploadedFiles;
    }

    /**
     * @inheritDoc
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        return $this->clone(['uploadedFiles' => $uploadedFiles]);
    }

    /**
     * @inheritDoc
     */
    public function getParsedBody()
    {
        $headerKey = $this->headerKeyMap['CONTENT-TYPE'] ?? 'Content-Type';
        $type = $this->headers[$headerKey] ?? 'plain/text';
        
        if ($type === 'application/x-www-form-urlencoded' || $type === 'multipart/form-data') {
            return $_POST;
        }
        
        if ($this->body->getSize() === 0) {
            return null;
        } else if ($type === 'application/json') {
            return json_decode($this->body->getContents());
        } else {
            return [$this->body->getContents()];
        }
    }

    /**
     * @inheritDoc
     */
    public function withParsedBody($data)
    {
        // ?
    }

    /**
     * @inheritDoc
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * @inheritDoc
     */
    public function getAttribute($name, $default = null)
    {
        return $this->attributes[$name] ?? $default;
    }

    /**
     * @inheritDoc
     */
    public function withAttribute($name, $value)
    {
        return $this->clone(['attributes' => array_merge($this->attributes, [$name => $value])]);
    }
    
    /**
     * @inheritDoc
     */
    public function withoutAttribute($name)
    {
        $attributes = $this->attributes;
        unset($attributes[$name]);
        return $this->clone(['attributes' => $attributes]);
    }

}

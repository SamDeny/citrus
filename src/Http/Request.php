<?php declare(strict_types=1);

namespace Citrus\Http;

use Citrus\Exceptions\CitrusException;
use Ds\Map;

class Request
{

    const REQUEST_HEADERS = [
        'A-IM',
        'Accept',
        'Accept-Charset',
        'Accept-Datetime',
        'Accept-Encoding',
        'Accept-Language',
        'Access-Control-Request-Method',
        'Access-Control-Request-Headers',
        'Authorization',
        'Cache-Control',
        'Connection',
        'Content-Encoding',
        'Content-Length',
        'Content-MD5',
        'Content-Type',
        'Cookie',
        'DNT',
        'Date',
        'Expect',
        'Forwarded',
        'From',
        'Front-End-Https',
        'HTTP2-Settings',
        'Host',
        'If-Match',
        'If-Modified-Since',
        'If-None-Match',
        'If-Range',
        'If-Unmodified-Since',
        'Max-Forwards',
        'Origin',
        'Pragma',
        'Prefer',
        'Proxy-Authorization',
        'Proxy-Connection',
        'Range',
        'Referer',
        'Referrer-Policy',
        'Save-Data',
        'Sec-Fetch-Site',
        'Sec-Fetch-Mode',
        'Sec-Fetch-User',
        'Sec-Fetch-Dest',
        'Sec-WebSocket-Key',
        'Sec-WebSocket-Extensions',
        'Sec-WebSocket-Accept',
        'Sec-WebSocket-Protocol',
        'Sec-WebSocket-Version',
        'Service-Worker-Navigation-Preload',
        'TE',
        'Trailer',
        'Transfer-Encoding',
        'Upgrade',
        'Upgrade-Insecure-Requests',
        'User-Agent',
        'Via',
        'Warning',
        'X-ATT-DeviceId',
        'X-Csrf-Token',
        'X-Forwarded-For',
        'X-Forwarded-Host',
        'X-Forwarded-Proto',
        'X-Http-Method-Override',
        'X-Requested-With',
        'X-UIDH',
        'X-Wap-Profile'
    ];

    /**
     * Create a new Request instance from GLOBALS.
     *
     * @return self
     */
    static public function createFromGlobals(): self
    {
        $request = $_SERVER['REQUEST_URI'];
        if (($index = strpos($request, '?')) !== false) {
            $request = substr($request, 0, $index);
        }

        return new self (
            $_SERVER['REQUEST_METHOD'] . ' ' . $request . ' ' . $_SERVER['SERVER_PROTOCOL'],
            getallheaders(),
            file_get_contents('php://input'),
            $_SERVER,
            $_COOKIE,
            $_GET,
            $_POST,
            $_FILES
        );
    }

    /**
     * Current request method.
     *
     * @var string
     */
    protected string $method;

    /**
     * Current request target.
     *
     * @var string
     */
    protected string $target;

    /**
     * Current request protocol.
     *
     * @var string
     */
    protected string $protocol;

    /**
     * Request headers key map.
     *
     * @var Map
     */
    protected Map $headersMap;

    /**
     * Current request headers.
     *
     * @var Map
     */
    protected Map $headers;

    /**
     * Current raw request body.
     *
     * @var ?string
     */
    protected ?string $raw;

    /**
     * Current parsed request body.
     *
     * @var ?string
     */
    protected ?string $body;

    /**
     * Current request server array.
     *
     * @var array
     */
    protected array $server;

    /**
     * Current request cookies array.
     *
     * @var array
     */
    protected array $cookies;

    /**
     * Current request query array.
     *
     * @var array
     */
    protected array $query;

    /**
     * Current request post array.
     *
     * @var array
     */
    protected array $post;

    /**
     * Current request files array.
     *
     * @var array
     */
    protected array $files;

    /**
     * Create a new Request.
     *
     * @param string|array $requestLine The first request line, either as whole
     *                     string `[METHOD] [TARGET] [PROTOCOL]` or as an list 
     *                     array [ METHOD, TARGET, PROTOCOL ].
     * @param array $headers All HTTP request headers with original cases, when
     *               possible, for example by using getallheaders().
     * @param ?string $body The raw body string or null if no body has been 
     *                sent. Usually file_get_contents('php://input').
     * @param array $server The _SERVER globals or a similar array.
     * @param array $cookies The _COOKIE globals or a similar array.
     * @param array $query The _GET globals or a similar array.
     * @param array $post The _POST globals or a similar array.
     * @param array $files The _FILES globals or a similar array.
     */
    public function __construct(
        string|array $request,
        array $headers,
        ?string $body,
        array $server,
        array $cookies,
        array $query,
        array $post,
        array $files
    ) {
        [$method, $target, $protocol] = is_string($request)? explode(' ', $request): $request;

        // Set Request Data
        $this->method = strtoupper($method);
        $this->target = $target;
        $this->protocol = strpos($protocol, 'HTTP/') === 0? substr($protocol, 5): $protocol;

        // Set Headers & Body
        $this->headersMap = new Map(array_combine(
            array_keys(array_change_key_case($headers, \CASE_UPPER)),
            array_keys($headers)
        )); 
        $this->headers = new Map($headers);
        $this->raw = $body;
        $this->body = empty($body)? '': null;

        // Set Global Data
        $this->server = $server;
        $this->cookies = $cookies;
        $this->query = $query;
        $this->post = $post;
        $this->files = $files;
    }

    /**
     * Get HTTP Request method.
     *
     * @return string
     */
    public function method(): string
    {
        return $this->method;
    }

    /**
     * Get HTTP Request target.
     *
     * @return string
     */
    public function target(): string
    {
        return $this->target;
    }

    /**
     * Get HTTP Request protocol.
     *
     * @return string
     */
    public function protocol(): string
    {
        return 'HTTP/' . $this->protocol;
    }

    /**
     * Get HTTP Request protocol version.
     *
     * @return string
     */
    public function protocolVersion(): string
    {
        return $this->protocol;
    }

    /**
     * Get all HTTP Request headers.
     *
     * @param bool $toArray Return all headers as array instead as Map.
     * @return array|Map
     */
    public function headers(bool $toArray = true): array|Map
    {
        return $toArray? $this->headers->toArray(): $this->headers;
    }

    /**
     * Check if specific HTTP Request header exists.
     *
     * @param string $name
     * @return boolean
     */
    public function hasHeader(string $name): bool
    {
        return $this->headersMap->get(strtoupper($name), false) !== false;
    }

    /**
     * Get specific HTTP Request headers.
     *
     * @param string $name
     * @return mixed
     */
    public function header(string $name): mixed
    {
        $key = $this->headersMap->get(strtoupper($name), '');
        return empty($key)? null: $this->headers->get($key, null);
    }

    /**
     * Get current Request remote address.
     *
     * @return ?string
     */
    public function remoteAddr(): ?string
    {
        $flags = FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6;
        if (($ip = $this->server['REMOTE_ADDR']) !== null) {
            if (filter_var($ip, FILTER_VALIDATE_IP, $flags) !== false) {
                return $ip;
            }
        }
        return null;
    }

    /**
     * Get current Request client IP address.
     *
     * @return ?string
     */
    public function clientIp(): ?string
    {
        $set = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR'
        ];
        $flags = FILTER_FLAG_IPV4 | FILTER_FLAG_IPV6;

        foreach ($set as $key) {
            if (!array_key_exists($key, $this->server)) {
                continue;
            }

            $value = $this->server[$key];
            if ($key !== 'REMOTE_ADDR' && $key !== 'HTTP_CLIENT_IP' && ($index = strpos($value, ',')) !== false) {
                $value = substr($value, 0, $index);
            }

            if (filter_var($value, FILTER_VALIDATE_IP, $flags) !== false) {
                return $value;
            }
        }
        
        return null;
    }

    /**
     * Get Do-Not-Track request setting when set.
     *
     * @return bool
     */
    public function doNotTrack(): bool
    {
        return $this->header('DNT') === '1';
    }

    /**
     * Get Do-Not-Track request setting when set.
     *
     * @return ?string
     */
    public function userAgent(): ?string
    {
        return $this->header('User-Agent');
    }

    /**
     * Get first Accept-Language value of current HTTP request.
     *
     * @return string|null
     */
    public function acceptLanguage(): ?string
    {
        $locales = $this->header('Accept-Language');
        if (empty($locales)) {
            return null;
        }

        $locales = trim(explode(',', trim($locales))[0]);
        if (($index = strpos($locales, ';')) !== false) {
            $locales = substr($locales, 0, $index);
        }
        
        return $locales;
    }

    /**
     * Get all Accept-Language values of current HTTP request.
     *
     * @return array
     */
    public function acceptLanguages(): array
    {
        $locales = $this->header('Accept-Language');
        if (empty($locales)) {
            return [];
        }

        $locales = array_map(function($value) {
            [$locale, $q] = array_pad(explode(';', trim($value), 2), 2, '1.0');
            if (($index = strpos($q, 'q=')) !== false) {
                $q = substr($q, $index+2);
            }
            return [$locale, floatval($q)];
        }, explode(',', $locales));
        usort($locales, fn($a, $b) => $a[1] === $b[1]? 0: ($a[1] < $b[1]? 1: -1));

        return $locales;
    }

    /**
     * Get Content-Type value of current HTTP request.
     *
     * @return ?string
     */
    public function contentType(): ?string
    {
        if (($contentType = $this->header('Content-Type')) === null) {
            return null;
        }

        if (($index = strpos($contentType, ';')) !== false) {
            $contentType = substr($contentType, 0, $index);
        }

        $contentType = trim($contentType);
        if (strpos($contentType, '/') >= 0) {
            return $contentType;
        } else {
            return null;
        }
    }

    /**
     * Check if current HTTP request could be AJAX / XHR.
     *
     * @return boolean
     */
    public function ajax(): bool
    {
        if (($this->header('X-RequestedWith') ?? '') === 'XMLHttpRequest') {
            return true;
        } else if (($this->header('Content-Type') ?? '') === 'application/json') {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Check if current HTTP request could be HTTPs.
     *
     * @return boolean
     */
    public function https(): bool
    {
        return (($this->server['HTTPS'] ?? 'off') !== 'off');
    }

    /**
     * Check if current HTTP request could be SSL / TLS.
     *
     * @return boolean
     */
    public function ssl(): bool
    {
        return $this->https();
    }

    /**
     * Get raw body of current HTTP Request.
     *
     * @return ?string
     */
    public function raw(): ?string
    {
        return $this->raw;
    }

    /**
     * Get parsed body of current HTTP Request.
     *
     * @return mixed
     */
    public function body(): mixed
    {
        if ($this->body === null) {
            if ($this->contentType() === 'application/json') {
                try {
                    $body = json_decode($this->rawBody, true);
                } catch(\Exception $e) {
                    throw new CitrusException('The current request body is not in a valid JSON format, error: ' . $e->getMessage());
                }
                $this->body = $body;
            } else {
                $this->body = $this->raw;
            }
        }
        return $this->body;
    }

    /**
     * Get currently assigned _SERVER value(s) for this request.
     * 
     * @param ?string $key
     * @return mixed
     */
    public function server(?string $key = null): mixed
    {
        return empty($key)? $this->server: $this->server[$key] ?? null;
    }

    /**
     * Get currently assigned _COOKIE value(s) for this request.
     * 
     * @return array
     */
    public function cookies()
    {
        return $this->cookies;
    }

    /**
     * Get specific assigned _COOKIE value of this request.
     * 
     * @return array
     */
    public function cookie(string $key)
    {
        return $this->cookies[$key] ?? null;
    }

    /**
     * Get currently assigned _GET value(s) for this request.
     * 
     * @param ?string $key
     * @return mixed
     */
    public function query(?string $key = null): mixed
    {
        return empty($key)? $this->query: $this->query[$key] ?? null;
    }

    /**
     * Get currently assigned _POST value(s) for this request.
     * 
     * @param ?string $key
     * @return mixed
     */
    public function post(?string $key = null): mixed
    {
        return empty($key)? $this->post: $this->post[$key] ?? null;
    }

    /**
     * Get specific _POST / _FILE value for this request.
     *
     * @param string $key
     * @return mixed
     */
    public function field(string $key): mixed
    {
        return $this->post[$key] ?? $this->files[$key] ?? null;
    }

    /**
     * Get currently assigned _FILE value(s) for this request.
     *
     * @return array
     */
    public function files(): array
    {
        return $this->files;
    }

    /**
     * Get specific assigned _FILE value for this request.
     * 
     * @param string $key
     * @return ?File
     */
    public function file(string $key): ?File
    {
        return $this->files[$key] ?? null;
    }

}

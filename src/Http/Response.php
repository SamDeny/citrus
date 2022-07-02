<?php declare(strict_types=1);

namespace Citrus\Http;

use Citrus\Exceptions\CitrusException;
use DateTime;
use Ds\Map;

class Response
{

    const STATUS_CODES = [
        100 => 'Continue',
        101 => 'Switching Protocols',
        103 => 'Early Hints',
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        226 => 'IM Used',
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        306 => 'Switch Proxy',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        402 => 'Payment Required',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => 'I\'m a teapot',
        419 => 'Page Expired',              // [CUSTOM] Laravel: Used when an CSRF Token is missing or expired
        421 => 'Misdirected Request',
        425 => 'Too Early',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        431 => 'Request Header Fields Too Large',
        451 => 'Unavailable For Legal Reasons',
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
    ];

    const RESPONSE_HEADERS = [
        'Accept-CH',
        'Accept-CH-Lifetime',
        'Accept-Patch',
        'Accept-Ranges',
        'Access-Control-Allow-Origin',
        'Access-Control-Allow-Credentials',
        'Access-Control-Expose-Headers',
        'Access-Control-Max-Age',
        'Access-Control-Allow-Methods',
        'Access-Control-Allow-Headers',
        'Alt-Svc',
        'Age',
        'Allow',
        'Alt-Svc',
        'Cache-Control',
        'Clear-Site-Data',
        'Connection',
        'Content-Disposition',
        'Content-Encoding',
        'Content-Language',
        'Content-Length',
        'Content-Location',
        'Content-MD5',
        'Content-Range',
        'Content-Security-Policy',
        'Content-Type',
        'Cross-Origin-Embedder-Policy',
        'Cross-Origin-Opener-Policy',
        'Cross-Origin-Resource-Policy',
        'Cross-Origin-Resource-Policy',
        'Content-Security-Policy',
        'Content-Security-Policy-Report-Only',
        'Date',
        'Delta-Base',
        'ETag',
        'Expect-CT',
        'Expires',
        'Feature-Policy',
        'IM',
        'Last-Modified',
        'Link',
        'Location',
        'NEL',
        'P3P',
        'Permissions-Policy',
        'Pragma',
        'Preference-Applied',
        'Proxy-Authenticate',
        'Public-Key-Pins',
        'Refresh',
        'Report-To',
        'Retry-After',
        'Server',
        'Set-Cookie',
        'Status',
        'Strict-Transport-Security',
        'Timing-Allow-Origin',
        'Tk',
        'Trailer',
        'Transfer-Encoding',
        'Upgrade',
        'Vary',
        'Via',
        'WWW-Authenticate',
        'Warning',
        'X-Content-Duration',
        'X-Content-Type-Options',
        'X-Frame-Options',
        'X-Powered-By',
        'X-Redirect-By',
        'X-UA-Compatible',
        'X-XSS-Protection',
        'X-Content-Security-Policy',
        'X-WebKit-CSP'
    ];


    /**
     * Send the HTTP response gz-compressed
     *
     * @var boolean
     */
    protected bool $compressed = false;
    
    /**
     * Current HTTP Response protocol version.
     *
     * @var string
     */
    protected string $protocol = '1.1';
    
    /**
     * Current HTTP Response status code.
     *
     * @var int
     */
    protected int $status = 200;
    
    /**
     * Current HTTP Response status phrase.
     *
     * @var string
     */
    protected string $phrase = 'OK';
    
    /**
     * Response headers key map.
     *
     * @var Map
     */
    protected Map $headerKeys;
    
    /**
     * Current HTTP Response headers.
     *
     * @var Map
     */
    protected Map $headers;
    
    /**
     * Current HTTP Response Content-Type value.
     *
     * @var string
     */
    protected string $contentType = 'text/plain';
    
    /**
     * Current HTTP Response Content-Type charset value.
     *
     * @var string
     */
    protected string $contentCharset = 'utf-8';

    /**
     * Current Expires RFC-7231 valid date/time string.
     *
     * @var boolean
     */
    protected bool $expires = false;
    
    /**
     * Current Cache-Control header values.
     *
     * @var boolean
     */
    protected bool $control = false;

    /**
     * Send the HTTP response with cache-header.
     *
     * @var boolean
     */
    protected bool $caching = false;

    /**
     * Current E-Tag value or boolean state.
     *
     * @var mixed
     */
    protected mixed $etag = false;

    /**
     * E-Tag Weak Type value.
     *
     * @var boolean
     */
    protected bool $etagWeak = false;

    /**
     * E-Tag Genrator based on the response content.
     *
     * @var mixed
     */
    protected mixed $etagHandler = null;
    
    /**
     * Current raw HTTP response body.
     *
     * @var mixed
     */
    protected mixed $raw = null;
    
    /**
     * Current parsed HTTP response body.
     *
     * @var string
     */
    protected string $body = '';

    /**
     * Create a new Response
     *
     * @param integer $status
     * @param ?string $phrase
     * @param array $headers
     */
    public function __construct(int $status = 200, ?string $phrase = null, array $headers = [])
    {
        $this->setStatus($status, $phrase);

        $this->headersMap = new Map(array_combine(
            array_map('strtoupper', self::RESPONSE_HEADERS),
            self::RESPONSE_HEADERS
        )); 
        $this->headers = new Map($headers);
    }

    /**
     * Magic String function to print Response HTTP conform.
     *
     * @return string
     */
    public function __toString()
    {
        $printed = ob_get_contents();
        ob_end_clean();
        
        $content = $printed . $this->body;
        $contentType = $this->contentType . ($this->contentCharset? ' charset=' . $this->contentCharset: '');

        // Set Compression
        if ($this->compressed) {
            if (ini_get('zlib.output_compression') === '1') {
                throw new CitrusException('You cannot use gzip and zlib.output_compression at the same time.');
            } else {
                if (!ob_start('ob_gzhandler')) {
                    ob_start();
                }
            }
        } else {
            ob_start();
        }

        // Set Basic HTML Headers
        if (!headers_sent()) {
            header("HTTP/{$this->protocol} {$this->status} {$this->phrase}");
            header("Content-Type: {$contentType}");
            header("Content-Length: ". strlen($content));

            // Set Caching Header
            if ($this->caching) {
                $expires = $this->getHeader('Expires') ?? $this->expires ?? null;
                $control = $this->getHeader('Cache-Control') ?? $this->control ?? null;
                if (empty($expires) || empty($control)) {
                    throw new CitrusException('The Expires and Cache-Control data must be set when caching is enabled.');
                }

                header("Cache-Control: {$control}");
                header("Expires: {$expires}");
            } else {
                header('Cache-Control: no-cache, must-revalidate');
                header('Expires: Fri, 01 Jan 1990 00:00:00 GMT');
                header('Pragma: no-cache');
            }

            // Set ETag
            if ($this->etag) {
                if ($this->etag === true) {
                    if (!$this->etagHandler || !is_callable($this->etagHandler)) {
                        throw new CitrusException('The ETag header requires either a callable function or a custom ID.');
                    }
                    $etag = call_user_func($this->etagHandler, $content);
                } else {
                    $etag = $this->etag;
                }
                header('ETag: '. ($this->etagWeak? 'W/': '') .'"'. $etag .'"');
            }

            // Loop other Headers
            foreach ($this->getHeaders() AS $header => $values) {
                if(in_array($header, ['Content-Type', 'Content-Length', 'Cache-Control', 'Expires', 'Pragma'])) {
                    continue; // Skip Managed Headers
                }

                if (is_array($values)) {
                    array_map(fn($val) => header("$header: $val", false), $values);
                } else {
                    header("$header: $values");
                }
            }
        }

        // Print Content
        return $content;
    }

    /**
     * Send HTTP response GZ-compressed
     *
     * @return self
     */
    public function enableCompression(): self
    {
        $this->compressed = true;
        return $this;
    }

    /**
     * Send HTTP response without GZ-compression
     *
     * @return self
     */
    public function disableCompression(): self
    {
        $this->compressed = false;
        return $this;
    }

    /**
     * Enable Caching-Headers for HTTP response.
     *
     * @return self
     */
    public function enableCaching(): self
    {
        $this->caching = true;
        return $this;
    }

    /**
     * Disable Caching-Headers for HTTP response.
     *
     * @return self
     */
    public function disableCaching(): self
    {
        $this->caching = false;
        return $this;
    }

    /**
     * Get currently set HTTP response protocol version.
     *
     * @return string
     */
    public function getProtocolVersion(): string
    {
        return $this->protocol;
    }

    /**
     * Set HTTP response protocol version.
     *
     * @param string $version
     * @return self
     */
    public function setProtocolVersion(string $version): self
    {
        $this->protocol = $version;
        return $this;
    }

    /**
     * Get currently set HTTP response status code.
     *
     * @return integer
     */
    public function getStatusCode(): int
    {
        return $this->status;
    }

    /**
     * Get currently set HTTP response status phrase.
     *
     * @return string
     */
    public function getStatusPhrase(): string
    {
        return $this->phrase;
    }

    /**
     * Set HTTP response protocol version.
     *
     * @param integer $status
     * @param string|null $phrase
     * @return self
     */
    public function setStatus(int $status, ?string $phrase = null): self
    {
        if (empty($phrase)) {
            if (!isset(self::STATUS_CODES[$status])) {
                throw new CitrusException('The status phrase cannot be empty, when the status code is not supported.');
            }
            $phrase = self::STATUS_CODES[$status]; 
        }
        $this->status = $status;
        $this->phrase = $phrase;
        return $this;
    }

    /**
     * Get all currently set HTTP response headers.
     *
     * @param bool $toArray
     * @return array
     */
    public function getHeaders(bool $toArray = true): array|Map
    {
        return $toArray? $this->headers->toArray(): $this->headers;
    }

    /**
     * Get specific currently set HTTP response headers.
     *
     * @param string $name
     * @return null|string|array
     */
    public function getHeader(string $name): null|string|array
    {
        $key = $this->headersMap->get(strtoupper($name));
        return empty($key)? null: $this->headers->get($key, null);
    }

    /**
     * Check if specific HTTP Request header exists.
     *
     * @param string $name
     * @return boolean
     */
    public function hasHeader(string $name): bool
    {
        $key = $this->headersMap->get(strtoupper($name));
        return empty($key)? false: $this->headers->hasKey($key);
    }

    /**
     * Set HTTP response header.
     *
     * @param string $name
     * @param string $value
     * @param boolean $add
     * @return self
     */
    public function setHeader(string $name, string $value, bool $add = false): self
    {
        $key = $this->headersMap->get(strtoupper($name));
        if (empty($key)) {
            $this->headersMap->set(strtoupper($name), $name);
            $key = $name;
        }

        if ($this->headers->hasKey($key) && $add) {
            $prev = $this->headers[$key];
            $this->headers[$key] = is_array($prev)? array_merge($prev, [$value]): [$prev, $value];
        } else {
            $this->headers[$key] = $value;
        }

        return $this;
    }

    /**
     * Get currently set Content-Type value.
     *
     * @return void
     */
    public function getContentType(): string
    {
        return $this->contentType;
    }

    /**
     * Get currently set Content-Type charset value.
     *
     * @return void
     */
    public function getContentCharset(): string
    {
        return $this->contentType;
    }

    /**
     * Set Content-Type value and charset.
     *
     * @param string $type
     * @param string $charset
     * @return self
     */
    public function setContentType(string $type, string $charset = 'utf-8'): self
    {
        $this->contentType = $type;
        $this->contentCharset = $charset;
        return $this;
    }

    /**
     * Set Caching Header
     *
     * @param integer|string|DateTime $expires
     * @param string $control
     * @return self
     */
    public function setCaching(int|string|DateTime $expires, string $control): self
    {
        if ($expires instanceof DateTime) {
            $expires = $expires->format(DateTime::RFC7231);
        } else if (is_int($expires)) {
            $expires = date(DateTime::RFC7231, $expires);
        }

        $this->expires = $expires;
        $this->control = $control;
        $this->caching = true;
        return $this;
    }

    /**
     * Unset Caching Header
     *
     * @return self
     */
    public function unsetCaching(): self
    {
        $this->expires = null;
        $this->control = null;
        $this->caching = false;
        return $this;
    }

    /**
     * Manually set an E-Tag
     *
     * @param string $etag
     * @param boolean $weak
     * @return self
     */
    public function setEtag(string $etag, bool $weak = false): self
    {
        $this->etag = $etag;
        $this->etagWeak = $weak;
        return $this;
    }

    /**
     * Generate E-Tag based on Content
     *
     * @param string|callable|\Closure $handler
     * @param boolean $weak
     * @return self
     */
    public function generateEtag(string|callable|\Closure $handler = 'sha1', bool $weak = false): self
    {
        $this->etag = true;
        $this->etagWeak = $handler;
        $this->etagHandler = $handler;
        return $this;
    }

    /**
     * Unset E-Tag Header
     *
     * @return self
     */
    public function unsetEtag(): self
    {
        $this->etag = false;
        return $this;
    }

    /**
     * Get raw / unparsed previously set Body.
     * 
     * @return string
     */
    public function getRaw(): string
    {
        return $this->raw;
    }

    /**
     * Get stringified / parsed previously set Body.
     * 
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Set response body.
     *
     * @param string $content
     * @return self
     */
    public function setBody(string $content): self
    {
        $this->raw = $content;
        $this->body = $content;
        return $this;
    }

    /**
     * Set HTML body.
     *
     * @param string $content
     * @param boolean $prevent
     * @return self
     */
    public function setHTML(string $content, bool $prevent = false): self
    {
        $this->raw = $content;
        $this->body = $content;

        // Change the content type only, when not already changed (text/plain is the default value)
        if ($this->contentType === 'text/plain' && $prevent !== true) {
            $this->setContentType('text/html');
        }

        return $this;
    }

    /**
     * Set XML body.
     *
     * @param string $content
     * @param boolean $prevent
     * @return self
     */
    public function setXML(string $content, bool $prevent = false): self
    {
        $this->raw = $content;
        $this->body = $content;

        // Change the content type only, when not already changed (text/plain is the default value)
        if ($this->contentType === 'text/plain' && $prevent !== true) {
            $this->setContentType('application/xml');
        }

        return $this;
    }

    /**
     * Set JSON body.
     *
     * @param array|object $content
     * @param boolean $prevent
     * @return self
     */
    public function setJSON(array|object $content, bool $prevent = false): self
    {
        $this->raw = $content;
        $this->body = json_encode($content);

        // Change the content type only, when not already changed (text/plain is the default value)
        if ($this->contentType === 'text/plain' && $prevent !== true) {
            $this->setContentType('application/json');
        }

        return $this;
    }

}

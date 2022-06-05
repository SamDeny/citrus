<?php declare(strict_types=1);

namespace Citrus\Http\Messages;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @internal Used internally to fulfill PSR-7.
 */
class HttpResponse extends HttpMessage implements ResponseInterface
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
     * Find Status Code
     *
     * @param string $phrase
     * @return integer
     */
    static public function findStatusCode(string $phrase): ?int
    {
        static $statusCodesReversed;

        if (empty($statusCodesReversed)) {
            $statusCodesReversed = array_change_key_case(array_flip(self::STATUS_CODES));
        }

        return $statusCodesReversed[strtolower($phrase)] ?? null;
    }

    /**
     * Find Status Phrase
     *
     * @param string $code
     * @return string
     */
    static public function findStatusPhrase(int $code): ?string
    {
        return self::STATUS_CODES[$code] ?? null;
    }

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
        static $responseHeaderCased;
        
        if (empty($responseHeaderCased)) {
            $responseHeaderCased = array_combine(array_map(fn($val) => strtolower($val), self::RESPONSE_HEADERS), self::RESPONSE_HEADERS);
        }

        return $requestCased[strtolower($header)] ?? ($returnInputOnFailure? $header: null);
    }

    /**
     * Create a new Response instance.
     *
     * @param string $protocol
     * @param array $headers
     * @param ?StreamInterface $body
     * @param int $statusCode
     * @param string $statusPhrase
     */
    public function __construct(
        string $protocol,
        array $headers,
        StreamInterface $body,
        int $statusCode = 200,
        string $statusPhrase = ''
    ) {
        parent::__construct($protocol, $headers, $body);

        $this->statusCode = $statusCode;
        $this->statusPhrase = !empty($statusPhrase)? $statusPhrase: self::findStatusPhrase($statusCode);
    }

    /**
     * @inheritDoc
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @inheritDoc
     */
    public function withStatus($code, $phrase = '')
    {
        $phrase = !empty($phrase)? $phrase: self::findStatusPhrase($code);

        if (empty($phrase)) {
            throw new \InvalidArgumentException('The passed status code is invalod or not supported');
        }

        return $this->clone(['statusCode' => $code, 'statusPhrase' => $phrase]);
    }

    /**
     * @inheritDoc
     */
    public function getReasonPhrase()
    {
        return $this->statusPhrase;
    }

}

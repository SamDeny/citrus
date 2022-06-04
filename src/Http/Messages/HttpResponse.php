<?php declare(strict_types=1);

namespace Citrus\Http\Messages;

use Citrus\Contracts\HttpResponseCodes;
use Citrus\Contracts\HttpResponseHeaders;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * @internal Used internally to fulfill PSR-7.
 */
class HttpResponse extends HttpMessage implements HttpResponseCodes, HttpResponseHeaders, ResponseInterface
{

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

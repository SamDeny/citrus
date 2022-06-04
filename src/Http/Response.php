<?php declare(strict_types=1);

namespace Citrus\Http;

use Citrus\Http\Messages\HttpResponse;
use Psr\Http\Message\StreamInterface;

class Response extends HttpResponse
{

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
        array $headers = [],
        ?StreamInterface $body = null,
        int $statusCode = 200,
        string $statusPhrase = ''
    ) {
        parent::__construct(
            $protocol, 
            $headers, 
            $body ?? Stream::createForResponse(),
            $statusCode,
            $statusPhrase
        );
    }

}

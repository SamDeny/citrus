<?php declare(strict_types=1);

namespace Citrus\Http;

use Citrus\Http\Messages\HttpStream;

class Stream extends HttpStream
{

    /**
     * Create Stream instance from Request body.
     *
     * @return static
     */
    static public function createFromRequest()
    {
        return new self(fopen('php://input', 'r'));
    }

    /**
     * Create Stream instance for Response body.
     *
     * @return static
     */
    static public function createForResponse()
    {
        return new self(fopen('php://output', 'w'));
    }

}

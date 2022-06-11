<?php declare(strict_types=1);

namespace Citrus\Exceptions;

class ParserException extends \Exception
{

    /**
     * Create a new Parser Exception
     *
     * @param string $message
     * @param array $attributes
     * @param integer $code
     * @param \Throwable|null $previous
     */
    public function __construct(string $message, array $attributes = [], int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->parser = $attributes;
    }
    
}

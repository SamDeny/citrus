<?php declare(strict_types=1);

namespace Citrus\Structures;

use Citrus\Concerns\Stack;

class ArrayStack extends Stack
{

    /**
     * Create a new custom Array Stack instance.
     *
     * @param array $stack
     * @param string|null $separator
     * @param boolean $caseSensitive
     */
    public function __construct(
        array $stack = [], 
        ?string $separator = '.', 
        bool $caseSensitive = false, 
        bool $readonly = false
    ) {
        $this->stack = $stack;
        $this->separator = $separator;
        $this->caseSensitive = $caseSensitive;
        $this->readonly = $readonly;
    }

}

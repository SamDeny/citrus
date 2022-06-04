<?php declare(strict_types=1);

namespace Citrus\Http\Stacks;

use Citrus\Concerns\Stack;

class HeaderStack extends Stack 
{

    /**
     * @inheritDoc
     */
    protected $caseSensitive = false;

}

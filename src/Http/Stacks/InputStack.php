<?php declare(strict_types=1);

namespace Citrus\Http\Stacks;

use Citrus\Concerns\Stack;

class InputStack extends Stack 
{

    /**
     * @inheritDoc
     */
    protected bool $readonly = true;

}

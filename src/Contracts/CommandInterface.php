<?php declare(strict_types=1);

namespace Citrus\Contracts;

interface CommandInterface
{

    /**
     * Describe the command including all available methods and arguments.
     *
     * @return array
     */
    static public function describe(): array;

}

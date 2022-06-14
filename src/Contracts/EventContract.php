<?php declare(strict_types=1);

namespace Citrus\Contracts;

interface EventContract
{

    /**
     * Return Event Type, usually the class name.
     *
     * @return string
     */
    public function getType(): string;

    /**
     * Get specific passed or filtered Arguments.
     *
     * @return mixed
     */
    public function getArgument(int $num): mixed;

    /**
     * Get passed or filtered Arguments.
     *
     * @return array
     */
    public function getArguments(): array;

}

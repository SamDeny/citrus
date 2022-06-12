<?php declare(strict_types=1);

namespace Citrus\Events;

use Citrus\Contracts\EventContract;

class GenericEvent implements EventContract
{

    /**
     * Initial Event arguments
     *
     * @var array
     */
    protected array $initials = [];

    /**
     * Event arguments
     *
     * @var array
     */
    protected array $arguments = [];
    
    /**
     * Create a new Event.
     *
     * @param array $args
     */
    public function __construct(array $args = [])
    {
        $this->initials = $args;
        $this->arguments = $args;
    }

    /**
     * Receive an event argument.
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->arguments[$name] ?? null;
    }

    /**
     * Set an event argument.
     *
     * @param string $name
     * @param mixed $value
     * @return void
     * @throws InvalidArgumentException The '%s' event does not support filtering the initial arguments.
     */
    public function __set(string $name, mixed $value): void
    {
        $class = $this::class;
        throw new \InvalidArgumentException("The '$class' event does not support filtering the initial arguments.");
    }

    /**
     * Return Event Type, usually the class name.
     *
     * @return string
     */
    public function getType(): string
    {
        return static::class;
    }

    /**
     * Get Arguments
     *
     * @return array
     */
    public function getArguments(): array
    {
        return $this->arguments;
    }

    /**
     * Get initial Arguments
     *
     * @return array
     */
    public function getInitialArguments(): array
    {
        return $this->initials;
    }

}

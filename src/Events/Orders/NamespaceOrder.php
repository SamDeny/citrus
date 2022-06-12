<?php declare(strict_types=1);

namespace Citrus\Events\Orders;

trait NamespaceOrder
{
    
    /**
     * The desired event type.
     *
     * @var string
     */
    protected string $type;

    /**
     * 
     *
     * @param array $args
     */
    public function __construct(string $type, array $args = [])
    {
        if (empty($type)) {
            throw new \InvalidArgumentException("The NamespaceOrder requires the first argument to be the desired event type.");
        }

        $this->type = $type;
        $this->initials = $args;
        $this->arguments = $args;
    }

    /**
     * Return Event Type, usually the class name.
     *
     * @return string
     */
    public function getType(): string
    {
        return static::class . '@' . $this->type;
    }

}

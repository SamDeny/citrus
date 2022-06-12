<?php declare(strict_types=1);

namespace Citrus\Events\Orders;

trait CancelableOrder
{

    /**
     * Switch to tell if propagation has been stopped or not.
     *
     * @var boolean
     */
    protected bool $propagationStopped = false;

    /**
     * Stops Propagation
     *
     * @return void
     */
    public function stopPropagation()
    {
        $this->propagationStopped = true;
    }

    /**
     * Check if propagation has been stopped or not.
     *
     * @return boolean
     */
    public function hasStopped(): bool
    {
        return $this->propagationStopped;
    }

}

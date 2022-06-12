<?php declare(strict_types=1);

namespace Citrus\Events\Orders;

trait PreventDefaultOrder
{

    /**
     * Switch to tell if preventDefault has been called or not.
     *
     * @var boolean
     */
    protected bool $isPrevented = false;

    /**
     * Prevent default operation
     *
     * @return void
     */
    public function preventDefault()
    {
        return $this->isPrevented = true;
    }

    /**
     * Check if peventDefault has been called or not.
     *
     * @return boolean
     */
    public function isPrevented(): bool
    {
        return $this->isPrevented;
    }

}

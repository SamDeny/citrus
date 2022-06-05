<?php declare(strict_types=1);

namespace Citrus\Framework;

use Ds\Map;
use Ds\PriorityQueue;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\EventDispatcher\ListenerProviderInterface;

class EventManager implements EventDispatcherInterface, ListenerProviderInterface
{

    /**
     * Event Listeners Map
     *
     * @var Map
     */
    protected Map $listeners;

    /**
     * Create a new Event Manager
     */
    public function __construct()
    {
        $this->listeners = new Map();
    }

    /**
     * Listen for a specific Event
     *
     * @param string $event
     * @param mixed $callback
     * @param integer $priority
     * @return void
     */
    public function listen(string $event, mixed $callback, int $priority = 100)
    {
        if (!$this->listeners->hasKey($event)) {
            $this->listeners->put($event, new PriorityQueue);
        }
        $this->listeners->get($event)->push($callback, $priority);
    }

    /**
     * Get Listeners for a specific Event
     *
     * @param object $event
     * @return iterable
     */
    public function getListenersForEvent(object $event): iterable
    {
        return $this->listeners->get($event::class, []);
    }

    /**
     * Dispatch a specific Event
     *
     * @param object $event
     * @return void
     */
    public function dispatch(object $event)
    {
        $listeners = $this->getListenersForEvent($event);

        foreach ($listeners AS $listen) {
            call_user_func($listen, $event);
        }
    }

}

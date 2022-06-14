<?php declare(strict_types=1);

namespace Citrus\Framework;

use Citrus\Contracts\EventContract;
use Citrus\Exceptions\CitrusException;
use Closure;
use Ds\Map;
use Ds\PriorityQueue;
use Ds\Set;

class EventManager
{

    /**
     * Current EventManager instance.
     *
     * @var self|null
     */
    static protected ?self $instance = null;

    /**
     * Get current EventManager instance.
     *
     * @return self
     * @throws CitrusException The EventManager has not been initialized yet.
     */
    static public function getInstance(): self
    {
        if (!self::$instance) {
            throw new CitrusException('The EventManager has not been initialized yet.');
        }
        return self::$instance;
    }

    
    /**
     * Citrus Application
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Event Listeners Map
     *
     * @var Map
     */
    protected Map $listeners;

    /**
     * Already called event types
     *
     * @var Set
     */
    protected Set $called;

    /**
     * Create a new Event Manager
     * 
     * @param Application $citrus
     */
    public function __construct(Application $citrus)
    {
        $this->app = $citrus;
        $this->listeners = new Map();
        $this->called = new Set();
    }

    /**
     * Add a new Event Listener.
     *
     * @param string $event The desired event to listen for, or the event class.
     * @param mixed $callback The desired callback function or closure, which 
     *              should be dispatched for this event.
     * @param integer $priority The desired priority of this event.
     * @return void
     */
    public function addListener(string $event, mixed $callback, int $priority = 100): void
    {
        if (!$this->listeners->hasKey($event)) {
            $this->listeners->put($event, new PriorityQueue);
        }
        $this->listeners[$event]->push($callback, $priority);
    }

    /**
     * Get Event Listeners based on the passed class.
     *
     * @param EventContract $class
     * @return array
     */
    public function getListeners(EventContract $event): array
    {
        $type = $event->getType();
        $result = [];

        // Get main Listeners
        if ($this->listeners->hasKey($type)) {
            $result = $this->listeners[$type]->toArray();
        }

        // Get Parent Listeners (NamespaceOrder)
        if (($index = strpos('@', $type)) !== false) {
            $type = substr($type, 0, $index);

            if ($this->listeners->hasKey($type)) {
                $result = array_merge($result, $this->listeners[$type]->toArray());
            }
        }

        // Return Listeners
        return $result;
    }

    /**
     * Dispatch an Event
     *
     * @param EventContract $event
     * @return EventContract
     */
    public function dispatch(EventContract $event): EventContract
    {
        $listeners = $this->getListeners($event);
        if (empty($listeners)) {
            return $event;
        }
        
        $orders = class_uses($event);
        if (in_array(UniqueOrder::class, $orders)) {
            if ($this->called->contains($event->getType())) {
                throw new CitrusException("The passed event type '". $event->getType() ."' cannot be called twice.");
            }
            $this->called->add($event->getType());
        }

        foreach ($listeners AS $listener) {
            if (is_a($listener, Closure::class)) {
                $this->app->call($listener, $event);
            } else {
                call_user_func($listener, $event);
            }

            // CancelableOrder
            if (in_array(CancelableOrder::class, $orders) && $this->event->hasStopped()) {
                break;
            }
        }

        return $event;
    }

}

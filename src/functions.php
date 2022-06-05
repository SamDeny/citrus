<?php declare(strict_types=1);

if (!function_exists('citrus')) {
    /**
     * Undocumented function
     *
     * @param mixed $handler
     * @return mixed
     * @throws CitrusException Unknown usage of the citrus() function.
     */
    function citrus(mixed $handler = null)
    {
        if ($handler === null) {
            return \Citrus\Framework\Application::getInstance();
        }

        if (is_string($handler)) {
            return \Citrus\Framework\Application::getInstance()->getContainer()->get($handler);
        }

        if ($handler instanceof \Closure) {
            return \Citrus\Framework\Application::getInstance()->callFunction($handler);
        }

        throw new \Citrus\Exceptions\CitrusException('Unknown usage of the citrus() function.');
    }
}

if (!function_exists('env')) {
    /**
     * Get Environment Data.
     *
     * @param string $key
     * @param mixed $default
     * @return void
     */
    function env(string $key, mixed $default = null)
    {
        return \Citrus\Framework\Configurator::getInstance()->getEnvironmentData($key, $default);
    }
}

if (!function_exists('config')) {
    /**
     * Get Configuration Data.
     *
     * @param string $key
     * @param mixed $default
     * @return void
     */
    function config(string $key, mixed $default = null)
    {
        return \Citrus\Framework\Configurator::getInstance()->getConfiguration($key, $default);
    }
}

if (!function_exists('path')) {
    /**
     * Generate an Application Path
     *
     * @param string $alias
     * @param string[] ...$paths
     * @return string
     */
    function path(string $alias, ...$paths): string
    {
        return \Citrus\Framework\Application::getInstance()->getPath($alias, ...$paths);
    }
}

if (!function_exists('event')) {
    /**
     * Listen for or Dispatch an event.
     *
     * @param string|Event The event (class) name to listen for or the event 
     *                     itself to dispatch.
     * @param mixed The event listener / callback handler to be called, when the 
     *              first value is the event (class) name.
     * @param ?int An optional priority value for the event listener / callback
     *             handler function. 
     * @return mixed
     */
    function event()
    {
        $citrus = \Citrus\Framework\Application::getInstance();
        $event = func_get_arg(0);

        if ($event instanceof \Citrus\Events\Event) {
            return $citrus->getEventManager()->dispatch($event);
        } else {
            return $citrus->getEventManager()->listen(...func_get_args());
        }
    }
}
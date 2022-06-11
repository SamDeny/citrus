<?php declare(strict_types=1);

if (!function_exists('citrus')) {
    /**
     * Undocumented function
     *
     * @return mixed
     * @throws CitrusException Unknown usage of the citrus() function.
     */
    function citrus()
    {
        if (func_num_args() === 0) {
            return \Citrus\Framework\Application::getInstance();
        }

        $args = func_get_args();
        $handler = array_shift($args);

        if (is_string($handler)) {
            return \Citrus\Framework\Application::getInstance()->make($handler, $args);
        }
        
        if ($handler instanceof \Closure) {
            return \Citrus\Framework\Application::getInstance()->call($handler);
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
        return \Citrus\Framework\Configurator::getInstance()->getEnvironment($key, $default);
    }
}

if (!function_exists('config')) {
    /**
     * Get Configuration Data.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    function config(string $key, mixed $default = null): mixed
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
    function path(...$paths): string
    {
        return \Citrus\Framework\Application::getInstance()->resolvePath(...$paths);
    }
}

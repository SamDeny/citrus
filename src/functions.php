<?php

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
        return \Citrus\Framework\Configurator::getInstance()->getEnv($key, $default);
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
        return \Citrus\Framework\Configurator::getInstance()->getConfig($key, $default);
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

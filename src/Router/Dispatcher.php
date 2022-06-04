<?php declare(strict_types=1);

namespace Citrus\Router;

use Citrus\Exceptions\DispatcherException;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class Dispatcher
{

    /**
     * Default available Route Patterns.
     *
     * @var array
     */
    static protected array $patterns = [
        ':any'      => '.*',
        ':each'     => '.+',
        ':num'      => '[0-9]+',
        ':alpha'    => '[a-zA-Z]+',
        ':alphanum' => '[0-9a-zA-Z]+',
        ':slug'     => '[0-9a-zA-Z_-]+',
        ':ns'       => '\@[a-zA-Z]{1}[0-9a-zA-Z_]+',
        ':path'     => '[^\/]+',
        ':id'       => '[0-9]+',
        ':uuid'     => '[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}',
        ':year'     => '(?:19|2[0-9])[0-9]{2}',
        ':month'    => '(?:0[1-9]|1[0-2])',
        ':day'      => '(?:0[1-9]|[1-2][0-9]|3[0-1])',
        ':date'     => '(?:19|2[0-9])[0-9]{2}\-(?:0[1-9]|1[0-2])\-(?:0[1-9]|[1-2][0-9]|3[0-1])',
        ':locale'   => '[a-z]{2}(?:\_[a-zA-Z]{2})?'
    ];

    /**
     * Add a new route pattern.
     *
     * @param string $name
     * @param string $regex
     * @return void
     * @throws DispatcherException The passed pattern name '%s' must start with a colon.
     * @throws DispatcherException The passed pattern name '%s' does already exist.
     */
    static public function addPattern(string $name, string $regex): void
    {
        if (strpos($name, ':') !== 0) {
            throw new DispatcherException("The passed pattern name '$name' must start with a colon.");
        }
        $name = strtolower($name);

        if (array_key_exists($name, self::$patterns)) {
            throw new DispatcherException("The passed pattern name '$name' does already exist.");
        }

        self::$patterns[$name] = $regex;
    }

    /**
     * Process an URI and return the most fitting Route object.
     *
     * @param string $method
     * @param string $uri
     * @return ?Route
     */
    public function process(string $method, string $uri): ?Route
    {
        return null;
    }

    /**
     * Dispatch a HTTP Request or Uri Interface
     *
     * @return void
     */
    public function dispatch(string $method, RequestInterface | UriInterface $request)
    {

    }

    /**
     * Dispatch a simple URI string.
     *
     * @param string $method
     * @param string $uri
     * @return void
     */
    public function dispatchUri(string $method, string $uri)
    {

    }

}

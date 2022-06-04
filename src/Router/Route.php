<?php declare(strict_types=1);

namespace Citrus\Router;

use Citrus\Exceptions\RouterException;

class Route
{

    /**
     * Collected routes assigned with name
     *
     * @var array
     */
    static protected $routes = [];

    /**
     * Get Route by assigned name.
     *
     * @param string $name
     * @return ?Route
     */
    static public function getRoute(string $name): ?Route
    {
        if (array_key_exists($name, self::$routes)) {
            return self::$routes[$name];
        } else {
            return null;
        }
    }
    

    /**
     * Supported Route methods
     *
     * @var array
     */
    protected array $methods;

    /**
     * Route Regular Expression
     *
     * @var string
     */
    protected string $route;

    /**
     * Assigned Route Handler
     *
     * @var array
     */
    protected mixed $handler;

    /**
     * API supported route (Route can be called via Rest-API)
     *
     * @var boolean
     */
    protected bool $api = false;

    /**
     * CLI supported route (Route can be called via Citrus CLI)
     *
     * @var boolean
     */
    protected bool $cli = false;

    /**
     * AJAX supported route (Route can be called via XHR Requests)
     *
     * @var boolean
     */
    protected bool $ajax = false;

    /**
     * Base URL on which this route applies
     *
     * @var ?string
     */
    protected ?string $url = null;

    /**
     * Unique route name
     *
     * @var ?string
     */
    protected ?string $name = null;

    /**
     * Assigned route middlewares
     *
     * @var array
     */
    protected array $middleware = [];

    /**
     * Assigned route parameters
     *
     * @var array
     */
    protected array $params = [];

    /**
     * Create a new Route.
     */
    public function __construct(array $methods, string $route, mixed $handler)
    {
        $this->methods = $methods;
        $this->route = $route;
        $this->handler = $handler;
    }

    /**
     * Public Getter
     *
     * @param string $name
     * @return mixed
     */
    public function __get(string $name): mixed
    {
        return $this->$name;
    }

    /**
     * Assign a unique Route Name.
     *
     * @param string $name
     * @return Route
     */
    public function name(string $name): Route
    {
        $name = strtolower(trim($name));

        // Unassign previous set name
        if (!is_null($this->name)) {
            if ($this->name === $name) {
                return $this;
            }
            if (array_key_exists($this->name, self::$routes)) {
                unset(self::$routes[$this->name]);
            }
            $this->name = null;

            // Using an empty string unsets the previously assigned name
            if ($name === '') {
                return $this;
            }
        }

        // Assign Name
        if (empty($name)) {
            throw new RouterException('The passed route name is invalid or empty.');
        }
        if (array_key_exists($name, self::$routes)) {
            throw new RouterException('The passed route name does already exist.');
        }
        self::$routes[$name] = $this;
        return $this;
    }

    /**
     * Assign a new Middleware.
     *
     * @param mixed $middleware
     * @return Route
     */
    public function middleware(mixed $middleware): Route
    {
        if (is_array($middleware)) {
            $this->middleware = array_merge($this->middleware, array_values($middleware));
        } else {
            $this->middleware = array_merge($this->middleware, [$middleware]);
        }
        return $this;
    }

    /**
     * Assign a new Validation Parameter - Validation Parameters are used to
     * pre-validate a route before it gets executed and thus also before the 
     * controller / handler is called.
     *
     * @param string $param
     * @param array $args
     * @return Route
     */
    public function param(string $param, array $args): Route
    {
        if (array_key_exists($param, $this->params)) {
            throw new RouterException('The passed parameter has already been assigned to this route.');
        }

        if (!class_exists($param)) {
            throw new RouterException('The passed route parameter does not exist.');
        }

        $this->params[$param] = $args;
        return $this;
    }

}

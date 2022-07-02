<?php declare(strict_types=1);

namespace Citrus\Router;

use Citrus\Contracts\MiddlewareContract;
use Citrus\Exceptions\RouterException;
use Citrus\Exceptions\RuntimeException;
use Citrus\Exceptions\UnmetContractException;

/**
 * Citrus Router / Route
 * The Citrus Router system is based on the nikic/FastRoute package and has 
 * been directly integrated here, since FastRoute isn't active developed.
 */
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
     * Raw Route String
     *
     * @var string
     */
    protected string $route;

    /**
     * Assigned Route Handler
     *
     * @var mixed
     */
    protected mixed $handler;

    /**
     * Parsed Route Details
     *
     * @var array
     */
    protected array $details;

    /**
     * Route Type 
     *
     * @var string
     */
    protected ?string $type = null;

    /**
     * Processed Route Regular Expression
     *
     * @var ?string
     */
    protected ?string $regex = null;

    /**
     * Processed Route Parameters
     *
     * @var array
     */
    protected array $params = [];

    /**
     * Additional Route Base Domain/Path
     *
     * @var ?string
     */
    protected ?string $base = null;

    /**
     * Additional Route Name
     *
     * @var ?string
     */
    protected ?string $name = null;

    /**
     * Additional Route Middleware
     *
     * @var ?string
     */
    protected ?array $middleware = [];

    /**
     * Create a new Route.
     *
     * @param string|array $methods The supported HTTP method(s).
     * @param string $route The raw route string.
     * @param mixed $handler The callable / Closure callback handler.
     * @param array $details The pased route details.
     * @param array $params The parameters provided by this route.
     */
    public function __construct(string|array $methods, string $route, mixed $handler, array $details = [])
    {
        $this->methods = is_string($methods)? [$methods]: $methods;
        $this->route = $route;
        $this->handler = $handler;
        $this->details = $details;
    }

    /**
     * Tests whether this route matches the passed string.
     * PS.: This method is NOT used by the Dispatcher.
     *
     * @param string $string
     * @return boolean
     */
    public function matches(string $string): bool
    {
        return (bool) preg_match('~^' . $this->regex . '$~', $string);
    }

    /**
     * Set route as static, if no type has been assigned yet.
     *
     * @return void
     */
    public function isStatic(): void
    {
        if ($this->type !== null) {
            return;
        }
        $this->type = 'static';
    }

    /**
     * Set route as dynamic, if no type has been assigned yet.
     *
     * @return void
     */
    public function isDynamic(): void
    {
        if ($this->type !== null) {
            return;
        }
        $this->type = 'dynamic';
    }

    /**
     * Get Route Methods
     *
     * @return array
     */
    public function methods(): array
    {
        return $this->methods;
    }

    /**
     * Get Raw Route
     *
     * @return string
     */
    public function route(): string
    {
        return $this->route;
    }

    /**
     * Get Route Handler
     *
     * @return mixed
     */
    public function handler(): mixed
    {
        return $this->handler;
    }

    /**
     * Get Route Details
     *
     * @return array
     */
    public function details(): array
    {
        return $this->details;
    }

    /**
     * Get|Set Processed Route Regular Expression
     *
     * @param null|string $regex
     * @return string|Route
     */
    public function regex(null|string $regex = null): string|Route
    {
        if (is_null($regex)) {
            return $this->regex;
        } else {
            $this->regex = $regex;
            return $this;
        }
    }

    /**
     * Get|Set Processed Route Parameters
     *
     * @param null|array $params
     * @return array
     */
    public function params(null|array $params = null): array|Route
    {
        if (is_null($params)) {
            return $this->params;
        } else {
            $this->params = $params;
            return $this;
        }
    }

    /**
     * Get|Set Route Base
     *
     * @param null|string $base
     * @return string|Route
     */
    public function base(null|string $base = null): string|Route
    {
        if (is_null($base)) {
            return $this->base;
        } else {
            if (($index = strpos($base, '://')) === 0) {
                $base = substr($base, $index+3);
            }

            $this->base = rtrim($base, '/ ');
            return $this;
        }
    }

    /**
     * Get|Set Route Name
     *
     * @param null|string $name
     * @return string|Route
     */
    public function name(null|string $name = null): string|Route
    {
        if (is_null($name)) {
            return $this->name;
        } else {
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
    }

    /**
     * Get|Set Route Middleware
     *
     * @param null|string|array $middleware
     * @param boolean $append
     * @return array|Route
     */
    public function middleware(null|string|array $middleware = null, bool $append = true): array|Route
    {
        if (is_null($middleware)) {
            return $this->middleware;
        } else {
            if (!is_array($middleware)) {
                $middleware = [$middleware];
            }

            // Validate Middleware
            array_map(function ($cls) {
                if (!class_exists($cls)) {
                    throw new RuntimeException("The passed route middleware '$cls' does not exist or could not be loaded.");
                }
                if (!in_array(MiddlewareContract::class, class_implements($cls))) {
                    throw new UnmetContractException("The passed route middleware '$cls' does not implement the '".MiddlewareContract::class."' contract.");
                }
            }, $middleware);

            // Append Middleware
            if ($append) {
                $this->middleware = array_merge($this->middleware, $middleware);
            } else {
                $this->middleware = $middleware;
            }
            return $this;
        }
    }

}

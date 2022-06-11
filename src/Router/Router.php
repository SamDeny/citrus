<?php declare(strict_types=1);

namespace Citrus\Router;

use Citrus\Contracts\SingletonInterface;
use Citrus\Exceptions\RouterException;
use Citrus\Http\Request;

/**
 * Citrus Router
 * The Citrus Router system is based on the nikic/FastRoute package and has 
 * been directly integrated here, since FastRoute isn't active developed.
 */
class Router implements SingletonInterface
{

    public const VARIABLE_REGEX = <<<'REGEX'
\{
    \s* ([a-zA-Z_][a-zA-Z0-9_-]*) \s*
    (?:
        : \s* ([^{}]*(?:\{(?-1)\}[^{}]*)*)
    )?
\}
REGEX;

    public const DEFAULT_DISPATCH_REGEX = '[^/]+';


    /**
     * Route Generator
     *
     * @var Generator
     */
    protected Generator $generator;

    /**
     * Route Dispatcher
     *
     * @var Dispatcher
     */
    protected Dispatcher $dispatcher;

    /**
     * Currently shared route attributes.
     *
     * @var array
     */
    protected array $shared = [];

    /**
     * Multi-dimensional array of previously shared route attributes / groups.
     *
     * @var array
     */
    protected array $previousGroups = [];

    /**
     * Create a new Route Collector instance.
     */
    public function __construct()
    {
        $this->generator = new Generator();
    }

    /**
     * Add a new Route.
     *
     * @param string|array $methods
     * @param string $routepath
     * @param mixed $handler
     * @return Route
     */
    public function route(string|array $methods, string $routepath, mixed $handler): Route
    {
        if (is_string($methods)) {
            $methods = [$methods];
        }

        $details = $this->parse($routepath);
        $route = new Route($methods, $routepath, $handler, $details);
        foreach ($route->methods() as $method) {
            foreach ($route->details() as $routeData) {
                $this->generator->addRoute($method, $routeData, $route);
            }
        }

        return $route;
    }

    /**
     * Parses a route string into multiple route data arrays.
     *
     * The expected output is defined using an example:
     *
     * For the route string "/fixedRoutePart/{varName}[/moreFixed/{varName2:\d+}]", if {varName} is interpreted as
     * a placeholder and [...] is interpreted as an optional route part, the expected result is:
     *
     * [
     *     // first route: without optional part
     *     [
     *         "/fixedRoutePart/",
     *         ["varName", "[^/]+"],
     *     ],
     *     // second route: with optional part
     *     [
     *         "/fixedRoutePart/",
     *         ["varName", "[^/]+"],
     *         "/moreFixed/",
     *         ["varName2", [0-9]+"],
     *     ],
     * ]
     *
     * Here one route string was converted into two route data arrays.
     *
     * @param string $route Route string to parse
     *
     * @return mixed[][] Array of route data arrays
     */
    protected function parse(string $route): array
    {
        $routeWithoutClosingOptionals = rtrim($route, ']');
        $numOptionals = strlen($route) - strlen($routeWithoutClosingOptionals);

        // Split on [ while skipping placeholders
        $segments = preg_split('~' . self::VARIABLE_REGEX . '(*SKIP)(*F) | \[~x', $routeWithoutClosingOptionals);

        if ($numOptionals !== count($segments) - 1) {
            // If there are any ] in the middle of the route, throw a more specific error message
            if (preg_match('~' . self::VARIABLE_REGEX . '(*SKIP)(*F) | \]~x', $routeWithoutClosingOptionals)) {
                throw new RouterException('Optional segments can only occur at the end of a route');
            }

            throw new RouterException("Number of opening '[' and closing ']' does not match");
        }

        $currentRoute = '';
        $routeDatas = [];

        foreach ($segments as $n => $segment) {
            if ($segment === '' && $n !== 0) {
                throw new RouterException('Empty optional part');
            }

            $currentRoute .= $segment;
            $routeDatas[] = $this->parsePlaceholders($currentRoute);
        }

        return $routeDatas;
    }

    /**
     * Parses a route string that does not contain optional segments.
     *
     * @return mixed[]
     */
    private function parsePlaceholders(string $route): array
    {
        if (! preg_match_all('~' . self::VARIABLE_REGEX . '~x', $route, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER)) {
            return [$route];
        }

        $offset = 0;
        $routeData = [];

        foreach ($matches as $set) {
            if ($set[0][1] > $offset) {
                $routeData[] = substr($route, $offset, $set[0][1] - $offset);
            }

            $routeData[] = [
                $set[1][0],
                isset($set[2]) ? trim($set[2][0]) : self::DEFAULT_DISPATCH_REGEX,
            ];

            $offset = $set[0][1] + strlen($set[0][0]);
        }

        if ($offset !== strlen($route)) {
            $routeData[] = substr($route, $offset);
        }

        return $routeData;
    }

    /**
     * Group multiple routes to share route attributes.
     *
     * @param array $sharedProps
     * @param callable $callback
     * @return void
     */
    public function group(array $sharedProps, callable $callback): static
    {
        if (!empty($this->shared)) {
            $this->previousGroups[] = $this->shared;
        }
        $this->shared = array_merge($this->shared, $sharedProps);
        
        call_user_func($callback, $this);

        if (!empty($this->previousGroups)) {
            $this->shared = array_pop($this->previousGroups);
        } else {
            $this->shared = [];
        }
        return $this;
    }

    /**
     * Add a new simple ANY Route.
     *
     * @param string $route
     * @param mixed $handler
     * @return Route
     */
    public function any(string $route, mixed $handler): Route
    {
        return $this->route('*', $route, $handler);
    }

    /**
     * Add a new simple HEAD Route.
     *
     * @param string $route
     * @param mixed $handler
     * @return Route
     */
    public function head(string $route, mixed $handler): Route
    {
        return $this->route('HEAD', $route, $handler);
    }

    /**
     * Add a new simple GET Route.
     *
     * @param string $route
     * @param mixed $handler
     * @return Route
     */
    public function get(string $route, mixed $handler): Route
    {
        return $this->route('GET', $route, $handler);
    }

    /**
     * Add a new simple POST Route.
     *
     * @param string $route
     * @param mixed $handler
     * @return Route
     */
    public function post(string $route, mixed $handler): Route
    {
        return $this->route('POST', $route, $handler);
    }

    /**
     * Add a new simple PUT Route.
     *
     * @param string $route
     * @param mixed $handler
     * @return Route
     */
    public function put(string $route, mixed $handler): Route
    {
        return $this->route('PUT', $route, $handler);
    }

    /**
     * Add a new simple PATCH Route.
     *
     * @param string $route
     * @param mixed $handler
     * @return Route
     */
    public function patch(string $route, mixed $handler): Route
    {
        return $this->route('PATCH', $route, $handler);
    }

    /**
     * Add a new simple DELETE Route.
     *
     * @param string $route
     * @param mixed $handler
     * @return Route
     */
    public function delete(string $route, mixed $handler): Route
    {
        return $this->route('DELETE', $route, $handler);
    }

    /**
     * Add a new simple OPTIONS Route.
     *
     * @param string $route
     * @param mixed $handler
     * @return Route
     */
    public function options(string $route, mixed $handler): Route
    {
        return $this->route('OPTIONS', $route, $handler);
    }

    /**
     * Add a special FORM Route.
     *
     * @param string $route
     * @param mixed $handler
     * @return Route
     */
    public function form(string $route, mixed $handler): Route
    {
        return $this->route(['GET', 'POST'], $route, $handler);
    }

    /**
     * Add a  new error handler.
     *
     * @param integer $error_code The desired error code.
     * @param mixed $handler The route handler, which is either a callable 
     *              string or array value or a controller name with namespace.
     * @return Router
     */
    public function error(int $error_code, mixed $handler): self
    {
        $this->errors[$error_code] = $handler;
        return $this;
    }

    /**
     * Dispatch a HTTP Request
     *
     * @param Request $request
     * @return void
     */
    public function dispatch(Request $request)
    {
        if (empty($this->dispatcher)) {
            $this->dispatcher = new Dispatcher($this->generator->getData());
        }

        $route = $this->dispatcher->dispatch(
            $request->method(),
            $request->target()
        );

        if (is_array($route)) {

        } else {
            return call_user_func($route->handler(), $request, ...$route->params());
        }
    }

}

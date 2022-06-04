<?php declare(strict_types=1);

namespace Citrus\Router;


class Collector
{

    /**
     * The currently shared route attributes.
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
     * Add a new Route.
     *
     * @param string|array $methods
     * @param string $route
     * @param mixed $handler
     * @return Route
     */
    public function route(string|array $methods, string $route, mixed $handler): Route
    {

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
     * Adds new routes based on the passed controller.
     *
     * @param string $base
     * @param string $controller
     * @return Route
     */
    public function ctrl(string $base, string $controller): Route
    {
        $interfaces = class_implements($controller);

    }

    /**
     * Add a  new error handler.
     *
     * @param integer $error_code The desired error code.
     * @param mixed $handler The route handler, which is either a callable 
     *              string or array value or a controller name with namespace.
     * @return Route
     */
    public function error(int $error_code, mixed $handler): Route
    {

    }

}

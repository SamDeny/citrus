<?php declare(strict_types=1);

namespace Citrus\Router;

/**
 * Citrus Router / Dispatcher
 * The Citrus Router system is based on the nikic/FastRoute package and has 
 * been directly integrated here, since FastRoute isn't active developed.
 * @internal Internal usage for the main Router class only.
 */
class Dispatcher
{

    /**
     * Static Route Map
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $staticRouteMap = [];

    /**
     * Variable Route Data
     * 
     * @var array<string, array<array{regex: string, suffix?: string, routeMap: array<int|string, array{0: mixed, 1: array<string, string>}>}>>
     */
    protected array $variableRouteData = [];

    /**
     * Crate a new Route Dispatcher
     * 
     * @param array{0: array<string, array<string, mixed>>, 1: array<string, array<array{regex: string, suffix?: string, routeMap: array<int|string, array{0: mixed, 1: array<string, string>}>}>>} $data
     */
    public function __construct(array $data = [])
    {
        [$this->staticRouteMap, $this->variableRouteData] = $data;
    }

    /**
     * Disaptch a variable route.
     * 
     * @param mixed[] $routeData
     * @return array{0: int, 1?: list<string>|mixed, 2?: array<string, string>}|null
     */
    protected function dispatchVariableRoute(array $routeData, string $uri): ?array
    {
        foreach ($routeData as $data) {
            if (! preg_match($data['regex'], $uri, $matches)) {
                continue;
            }

            [$route, $varNames] = $data['routeMap'][$matches['MARK']];

            $vars = [];
            $i = 0;
            foreach ($varNames as $varName) {
                $vars[$varName] = $matches[++$i];
            }

            return $route;
        }

        return null;
    }


    /**
     * Dispatches against the provided HTTP method verb and URI.
     *
     * @return Route|array
     */
    public function dispatch(string $httpMethod, string $uri): Route|array
    {
        if (isset($this->staticRouteMap[$httpMethod][$uri])) {
            $route = $this->staticRouteMap[$httpMethod][$uri];
            return $route;
        }

        $varRouteData = $this->variableRouteData;
        if (isset($varRouteData[$httpMethod])) {
            $route = $this->dispatchVariableRoute($varRouteData[$httpMethod], $uri);
            if ($route !== null) {
                return $route;
            }
        }

        // For HEAD requests, attempt fallback to GET
        if ($httpMethod === 'HEAD') {
            if (isset($this->staticRouteMap['GET'][$uri])) {
                $route = $this->staticRouteMap['GET'][$uri];
                return $route;
            }

            if (isset($varRouteData['GET'])) {
                $route = $this->dispatchVariableRoute($varRouteData['GET'], $uri);
                if ($route !== null) {
                    return $route;
                }
            }
        }

        // If nothing else matches, try fallback routes
        if (isset($this->staticRouteMap['*'][$uri])) {
            $route = $this->staticRouteMap['*'][$uri];
            return $route;
        }

        if (isset($varRouteData['*'])) {
            $route = $this->dispatchVariableRoute($varRouteData['*'], $uri);
            if ($route !== null) {
                return $route;
            }
        }

        // Find allowed methods for this URI by matching against all other HTTP methods as well
        $allowedMethods = [];
        foreach ($this->staticRouteMap as $method => $uriMap) {
            if ($method === $httpMethod || ! isset($uriMap[$uri])) {
                continue;
            }
            $allowedMethods[] = $method;
        }

        foreach ($varRouteData as $method => $routeData) {
            if ($method === $httpMethod) {
                continue;
            }

            $result = $this->dispatchVariableRoute($routeData, $uri);
            if ($result === null) {
                continue;
            }

            $allowedMethods[] = $method;
        }

        // If there are no allowed methods the route simply does not exist
        if ($allowedMethods !== []) {
            return [405, $allowedMethods];
        }
        return [404, null];
    }

}

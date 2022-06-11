<?php declare(strict_types=1);

namespace Citrus\Router;

use Citrus\Exceptions\RouterException;

/**
 * Citrus Router / Generator
 * The Citrus Router system is based on the nikic/FastRoute package and has 
 * been directly integrated here, since FastRoute isn't active developed.
 * @internal Internal usage for the main Router class only.
 */
class Generator
{

    /**
     * Static Route Storage
     *
     * @var array<string, array<string, mixed>>
     */
    protected array $staticRoutes = [];

    /**
     * Dynamic Route Storage
     *
     * @var array<string, array<string, Route>>
     */
    protected array $methodToRegexToRoutesMap = [];

    /**
     * Process Route Chunk
     *
     * @param array<string, Route> $regexToRoutesMap
     * @return array{regex: string, suffix?: string, routeMap: array<int|string, array{0: mixed, 1: array<string, string>}>}
     */
    protected function processChunk(array $regexToRoutesMap): array
    {
        $routeMap = [];
        $regexes = [];
        $markName = 'a';

        foreach ($regexToRoutesMap as $regex => $route) {
            $regexes[] = $regex . '(*MARK:' . $markName . ')';
            $routeMap[$markName] = [$route->handler, $route->variables];

            ++$markName;
        }

        $regex = '~^(?|' . implode('|', $regexes) . ')$~';

        return ['regex' => $regex, 'routeMap' => $routeMap];
    }

    /**
     * Adds a route to the data generator. The route data uses the same format 
     * that is returned by Router::parse().
     *
     * @param string $method The route method.
     * @param mixed[] $routeData The route data array.
     * @param Route $route The route instance.
     * @return void 
     */
    public function addRoute(string $httpMethod, array $routeData, Route $route): void
    {
        if ($this->isStaticRoute($routeData)) {
            $this->addStaticRoute($httpMethod, $routeData, $route);
        } else {
            $this->addVariableRoute($httpMethod, $routeData, $route);
        }
    }

    /**
     * Returns dispatcher data in some unspecified format, which
     * depends on the used method of dispatch.
     *
     * @return array{0: array<string, array<string, mixed>>, 1: array<string, array<array{regex: string, suffix?: string, routeMap: array<int|string, array{0: mixed, 1: array<string, string>}>}>>}
     */
    public function getData(): array
    {
        if ($this->methodToRegexToRoutesMap === []) {
            return [$this->staticRoutes, []];
        }

        return [$this->staticRoutes, $this->generateVariableRouteData()];
    }

    /**
     * Generate Variable Route Data
     *
     * @return array<string, array<array{regex: string, suffix?: string, routeMap: array<int|string, array{0: mixed, 1: array<string, string>}>}>>
     */
    private function generateVariableRouteData(): array
    {
        $data = [];
        foreach ($this->methodToRegexToRoutesMap as $method => $regexToRoutesMap) {
            $chunkSize = $this->computeChunkSize(count($regexToRoutesMap));
            $chunks = array_chunk($regexToRoutesMap, $chunkSize, true);
            $data[$method] = array_map([$this, 'processChunk'], $chunks);
        }

        return $data;
    }

    /**
     * Compute Chunk Size
     *
     * @param integer $count
     * @return integer
     */
    private function computeChunkSize(int $count): int
    {
        $numParts = max(1, round($count / 30));

        return (int) ceil($count / $numParts);
    }

    /**
     * Check for Static Route
     *
     * @param array<int, mixed> $routeData 
     * @return boolean
     */
    private function isStaticRoute(array $routeData): bool
    {
        return count($routeData) === 1 && is_string($routeData[0]);
    }

    /**
     * Add a new static route
     * 
     * @param string $method The route method.
     * @param array<int, mixed> $routeData The route details.
     * @param Route $route The route instance.
     * @return void
     */
    private function addStaticRoute(string $httpMethod, array $routeData, Route $route): void
    {
        $routeStr = $routeData[0];

        if (isset($this->staticRoutes[$httpMethod][$routeStr])) {
            throw new RouterException(sprintf(
                'Cannot register two routes matching "%s" for method "%s"',
                $routeStr,
                $httpMethod
            ));
        }

        if (isset($this->methodToRegexToRoutesMap[$httpMethod])) {
            foreach ($this->methodToRegexToRoutesMap[$httpMethod] as $route) {
                if ($route->matches($routeStr)) {
                    throw new RouterException(sprintf(
                        'Static route "%s" is shadowed by previously defined variable route "%s" for method "%s"',
                        $routeStr,
                        $route->regex,
                        $httpMethod
                    ));
                }
            }
        }

        $route->isStatic();
        $this->staticRoutes[$httpMethod][$routeStr] = $route;
    }

    /**
     * Add a new variable route
     * 
     * @param string $method The route method.
     * @param array<int, mixed> $routeData The route details.
     * @param Route $route The route instance.
     * @return void
     */
    private function addVariableRoute(string $httpMethod, array $routeData, Route $route): void
    {
        [$regex, $params] = $this->buildRegexForRoute($routeData);

        if (isset($this->methodToRegexToRoutesMap[$httpMethod][$regex])) {
            throw new RouterException(sprintf(
                'Cannot register two routes matching "%s" for method "%s"',
                $regex,
                $httpMethod
            ));
        }

        $route->isDynamic();
        $route->regex($regex);
        $route->params($params);
        $this->methodToRegexToRoutesMap[$httpMethod][$regex] = $route;
    }

    /**
     * Build Regular Expression for Route.
     * 
     * @param mixed[] $routeData
     * @return array{0: string, 1: array<string, string>}
     */
    private function buildRegexForRoute(array $routeData): array
    {
        $regex = '';
        $variables = [];
        foreach ($routeData as $part) {
            if (is_string($part)) {
                $regex .= preg_quote($part, '~');
                continue;
            }

            [$varName, $regexPart] = $part;

            if (isset($variables[$varName])) {
                throw new RouterException(sprintf(
                    'Cannot use the same placeholder "%s" twice',
                    $varName
                ));
            }

            if ($this->regexHasCapturingGroups($regexPart)) {
                throw new RouterException(sprintf(
                    'Regex "%s" for parameter "%s" contains a capturing group',
                    $regexPart,
                    $varName
                ));
            }

            $variables[$varName] = $varName;
            $regex .= '(' . $regexPart . ')';
        }

        return [$regex, $variables];
    }

    /**
     * Check if regular expression has capturing groups.
     *
     * @param string $regex
     * @return boolean
     */
    private function regexHasCapturingGroups(string $regex): bool
    {
        if (strpos($regex, '(') === false) {
            // Needs to have at least a ( to contain a capturing group
            return false;
        }

        // Semi-accurate detection for capturing groups
        return (bool) preg_match(
            '~
                (?:
                    \(\?\(
                  | \[ [^\]\\\\]* (?: \\\\ . [^\]\\\\]* )* \]
                  | \\\\ .
                ) (*SKIP)(*FAIL) |
                \(
                (?!
                    \? (?! <(?![!=]) | P< | \' )
                  | \*
                )
            ~x',
            $regex
        );
    }

}

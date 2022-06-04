<?php declare(strict_types=1);

namespace Citrus\Framework;

use Citrus\Console\Console;
use Citrus\Contracts\Runtime;
use Citrus\Exceptions\CitrusException;
use Citrus\Http\Request;
use DI\Container;
use DI\ContainerBuilder;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionFunction;

class Application
{

    /**
     * Environment Key.
     */
    const ENVIRONMENT_KEY = 'CRATE_ENV';

    /**
     * Citrus Version
     */
    const VERSION = '0.1.0';

    /**
     * Current Application instance.
     *
     * @var Application|null
     */
    static protected ?Application $instance = null;

    /**
     * Get current Application instance
     *
     * @return Application
     * @throws CitrusException The Application has not been initialized yet.
     */
    static public function getInstance(): Application
    {
        if (!self::$instance) {
            throw new CitrusException('The Application has not been initialized yet.');
        }
        return self::$instance;
    }


    /**
     * Root Application directory
     *
     * @var string
     */
    protected string $root;

    /**
     * Application Start Microtime
     *
     * @var float
     */
    protected float $startTime;

    /**
     * Application Configurator
     *
     * @var Configurator
     */
    protected Configurator $configurator;

    /**
     * Optional Runtime Service for this Application
     *
     * @var ?Runtime
     */
    protected ?Runtime $runtime = null;

    /**
     * Application Container
     *
     * @var ?Container
     */
    protected ?Container $container = null;

    /**
     * Application Container Builder
     *
     * @var ?ContainerBuilder
     */
    protected ?ContainerBuilder $containerBuilder = null;

    /**
     * Application Directories
     *
     * @var array
     */
    protected array $directories = [];

    /**
     * Application Directories
     *
     * @var array
     */
    protected array $factories = [];

    /**
     * Application Services
     *
     * @var array
     */
    protected array $services = [];

    /**
     * Create a new Citrus Application
     * 
     * @param string $root The root application folder.
     * @throws CitrusException The Application cannot be initialized twice.
     * @throws CitrusException The passed root path does not exist.
     */
    public function __construct(string $root)
    {
        if (self::$instance) {
            throw new CitrusException('The Application cannot be initialized twice.');
        }
        self::$instance = $this;

        if (($root = realpath($root)) === false) {
            throw new CitrusException('The passed root path does not exist.');
        }
        $this->root = $root;
        $this->startTime = floatval(microtime(true));
        $this->configurator = new Configurator;
    }

    /**
     * Get Citrus Application Version
     *
     * @return string
     */
    public function getVersion(): string
    {
        return self::VERSION;
    }

    /**
     * Get used PHP Version
     *
     * @return string
     */
    public function getPHPVersion(): string
    {
        return PHP_VERSION;
    }

    /**
     * Get Applcation Starttime
     *
     * @return float
     */
    public function getStartTime(): float
    {
        return $this->startTime;
    }

    /**
     * Load DotEnv Environment
     * 
     * @param ?string $path_or_file The path containing the .env file(s), the filepath directly or 
     *                null to create the filepath from the rootPath.
     * @return void
     */
    public function loadEnvironment(?string $path_or_file = null): void
    {
        if (is_null($path_or_file)) {
            $path_or_file = $this->root . DIRECTORY_SEPARATOR . '.env';
        }
        $this->configurator->registerEnv($_ENV, '_ENV');
        $this->configurator->registerEnv($_SERVER, '_SERVER');
        $this->configurator->loadEnv($path_or_file, 'CREATE_ENV', true, '_LOCAL');
    }

    /**
     * The current Environment on which Citrus is currently running
     *
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->configurator->getEnv(self::ENVIRONMENT_KEY, 'production');
    }

    /**
     * Check if the current environment is "Production".
     *
     * @return boolean
     */
    public function isProduction()
    {
        $env = strtolower($this->getEnvironment());
        return $env === 'production' || $env === 'prod';
    }

    /**
     * Check if the current environment is "Staging".
     *
     * @return boolean
     */
    public function isStaging()
    {
        $env = strtolower($this->getEnvironment());
        return $env === 'staging' || $env === 'stage';
    }

    /**
     * Check if the current environment is "Development".
     *
     * @return boolean
     */
    public function isDevelopment()
    {
        $env = strtolower($this->getEnvironment());
        return $env === 'development' || $env === 'dev';
    }

    /**
     * Set Application directory
     *
     * @param string $alias
     * @param string $path
     * @return void
     * @throws CitrusException The passed path '%s' for the alias '%s' does not exist and could not be created.
     */
    public function setDirectory(string $alias, string $path): void
    {
        if (strpos($path, '$') === 0) {
            $path = $this->root . DIRECTORY_SEPARATOR . substr($path, 1);
        }

        if (!file_exists($path)) {
            if (@mkdir($path, 077, true) === false) {
                throw new CitrusException("The passed path '$path' for the alias '$alias' does not exist and could not be created.");
            }
        }

        $this->directories[$alias] = $path;
    }

    /**
     * Set multiple Application directories
     *
     * @param array $directores
     * @return void
     */
    public function setDirectories(array $directores): void
    {
        array_walk($directores, fn($val, $key) => $this->setDirectory($key, $val));
    }

    /**
     * Check if an directory alias has been defined
     *
     * @param string $alias 
     * @return boolean
     */
    public function hasDirectory(string $alias): bool
    {
        return array_key_exists($alias, $this->directories);
    }

    /**
     * Generate a Path within the Applcations root folder
     *
     * @param string[] ...$paths
     * @return string
     */
    public function getRoot(...$paths): string
    {
        if (!empty($paths)) {
            return $this->root . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $paths);
        } else {
            return $this->root;
        }
    }

    /**
     * Generate a Path within the Application folder
     *
     * @param string $alias
     * @param string[] ...$paths
     * @return string
     * @throws CitrusException The passed alias '%s' does not exist.
     */
    public function getPath(string $alias, ...$paths): string
    {
        if ($alias === '$') {
            $alias = $this->root;
        } else {
            $alias = $this->directories[$alias] ?? null;
        }

        if (is_null($alias)) {
            throw new CitrusException("The passed path alias '" . func_get_arg(0) . "' does not exist.");
        }
        

        if (!empty($paths)) {
            return $alias . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $paths);
        } else {
            return $alias;
        }
    }

    /**
     * Initialize the PHP-DI's Container Builder
     *
     * @return void
     */
    public function initContainer()
    {
        $builder = new \DI\ContainerBuilder();

        // Register Core Definitions
        $builder->addDefinitions([
            \Citrus\Framework\Application::class    => $this,
            \Citrus\Framework\Configurator::class   => $this->configurator,
            \Citrus\Http\File::class                => \Citrus\Http\File::class,
            \Citrus\Http\Request::class             => \Citrus\Http\Request::class,
            \Citrus\Http\Response::class            => \Citrus\Http\Response::class,
            \Citrus\Http\Stream::class              => \Citrus\Http\Stream::class,
            \Citrus\Http\Uri::class                 => \Citrus\Http\Uri::class,
            \Citrus\Router\Collector::class         => \Citrus\Router\Collector::class,
            \Citrus\Router\Dispatcher::class        => \Citrus\Router\Dispatcher::class,
            \Citrus\Router\Route::class             => \Citrus\Router\Route::class
        ]);

        // Enable Caching
        if (!$this->isProduction() && $this->hasDirectory('cache')) {
            $builder->enableCompilation($this->getPath('cache'));
            $builder->writeProxiesToFile(true, $this->getPath('cache', 'proxies'));
        }

        // Set Builder
        $this->containerBuilder = $builder;
    }

    /**
     * Build the current PHP-DI Container
     *
     * @return void
     */
    public function buildContainer(): void
    {
        $this->container = $this->containerBuilder->build();
    }

    /**
     * Get the current PHP-DI Container when already build
     *
     * @return ?Container
     */
    public function getContainer(): ?Container
    {
        return $this->container ?? null;
    }

    /**
     * Set a Runtime Service which does some cool things.
     *
     * @param string $class
     * @return void
     * @throws CitrusException The passed class '%s' does not implement the Runtime contract.
     */
    public function useRuntimeService(string $class): void
    {
        if (!in_array(Runtime::class, class_implements($class))) {
            throw new CitrusException("The passed class '$class' does not implement the Runtime contract.");
        }
        $this->runtime = new $class($this);
        $this->runtime->init();
    }

    /**
     * Receive the registered Runtime Service
     *
     * @param string $class
     * @return ?Runtime
     */
    public function getRuntimeService(): Runtime
    {
        return $this->runtime;
    }

    /**
     * Register Factory
     *
     * @param string $alias
     * @param mixed $factory
     * @return void
     * @throws CitrusException The passed factory alias '%s' has already been registered.
     */
    public function registerFactory(string $alias, mixed $factory)
    {
        if (array_key_exists($alias, $this->services)) {
            throw new CitrusException("The passed factory alias '$alias' has already been registered.");
        }

        $this->factories[$alias] = $factory;

        if ($this->container) {
            $this->container->set($alias, $factory);
        } else {
            $this->containerBuilder->addDefinitions([$alias => $factory]);
        }
    }

    /**
     * Register multiple Factories
     *
     * @param array $factories
     * @return void
     */
    public function registerFactories(array $factories)
    {
        array_walk($factories, fn($val, $key) => $this->registerFactory($key, $val));
    }

    /**
     * Receive a registered factory.
     *
     * @param string $alias
     * @return void
     */
    public function factory(string $alias)
    {
        if ($this->container) {
            return $this->container->get($alias);
        } else {
            return $this->factories[$alias] ?? null;
        }
    }

    /**
     * Register Service
     *
     * @param string $alias
     * @param mixed $service
     * @return void
     * @throws CitrusException The passed service alias '%s' has already been registered.
     */
    public function registerService(string $alias, mixed $service)
    {
        if (array_key_exists($alias, $this->services)) {
            throw new CitrusException("The passed service alias '$alias' has already been registered.");
        }

        $this->servicesfactories[$alias] = $service;

        if ($this->container) {
            $this->container->set($alias, $service);
        } else {
            $this->containerBuilder->addDefinitions([$alias => $service]);
        }
    }

    /**
     * Register multiple Services
     *
     * @param array $services
     * @return void
     */
    public function registerServices(array $services)
    {
        array_walk($services, fn($val, $key) => $this->registerService($key, $val));
    }

    /**
     * Receive a registered service.
     *
     * @param string $alias
     * @return void
     */
    public function service(string $alias)
    {
        if ($this->container) {
            return $this->container->get($alias);
        } else {
            return $this->services[$alias] ?? null;
        }
    }








    /**
     * Load Configuration File
     *
     * @param string $path_or_file The path containing the configuration .php files or the single
     *               filepath directly.
     * @param string|null $alias An additional alias used for single filepaths or null to use the
     *                    filename without extension itself.
     * @return void
     */
    public function configure(string $path_or_file, ?string $alias = null): void
    {
        
    }

    /**
     * Call Function via Citrus
     *
     * @param \Closure $func
     * @return void
     */
    public function callFunction(\Closure $func)
    {
        if ($this->container instanceof \Invoker\InvokerInterface) {
            $this->container->call($func);
        } else {
            $reflection = new ReflectionFunction($func);
            var_dump(strval($reflection->getParameters()[0]->getType()));
        }
    }

    /**
     * Finish the Citrus Runtime Setup
     *
     * @return void
     */
    public function finish()
    {
        if (!$this->container && $this->containerBuilder) {
            $this->buildContainer();
        }

        if ($this->runtime) {
            $this->runtime->finish();
        }
    }

    /**
     * Run the Application using / generating a ServerRequest.
     *
     * @param ServerRequestInterface|null $request
     * @return void
     */
    public function run(?ServerRequestInterface $request = null)
    {
        if ($request === null) {
            $request = Request::createFromGlobals();
        }

        if (!($request instanceof ServerRequestInterface)) {
            throw new CitrusException('The run method cannot be executed using a non-server request.');
        }

        print('YaY in ' . (microtime(true) - $this->startTime) . ' seconds');
    }

    /**
     * Run the Application using / generating a normal (CLI) Request.
     *
     * @param ServerRequestInterface|null $request
     * @return void
     */
    public function execute($request = null)
    {
        $console = new Console();
        $console->execute();
    }

}

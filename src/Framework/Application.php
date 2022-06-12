<?php declare(strict_types=1);

namespace Citrus\Framework;

use Citrus\Console\Console;
use Citrus\Contracts\EventContract;
use Citrus\Events\ApplicationEvent;
use Citrus\Http\Request;
use Citrus\Router\Router;
use Citrus\Exceptions\CitrusException;
use Citrus\FileSystem\Parser\INIParser;
use Citrus\FileSystem\Parser\JSONParser;
use Citrus\FileSystem\Parser\PHPParser;
use Citrus\FileSystem\Parser\YAMLParser;
use Citrus\Http\Response;

class Application
{

    /**
     * Citrus Version
     */
    const VERSION = '0.1.0';

    /**
     * Citrus Application.
     *
     * @var Application
     */
    static protected Application $instance;

    /**
     * Get Citrus Application instance
     *
     * @return Application
     * @throws CitrusException The Citrus Application has not been initialized yet.
     */
    static public function getInstance(): Application
    {
        if (!self::$instance) {
            throw new CitrusException('The Citrus Application has not been initialized yet.');
        }
        return self::$instance;
    }


    /**
     * Application root path.
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
     * Application Container
     *
     * @var Container
     */
    protected Container $container;

    /**
     * Application Event Manager
     *
     * @var EventManager
     */
    protected EventManager $eventManager;


    /**
     * Application core paths.
     *
     * @var array
     */
    protected array $paths = [];

    /**
     * Create a new Citrus Application.
     *
     * @param string $root
     * @throws CitrusException The Citrus Application cannot be initialized twice.
     * @throws CitrusException The passed root path '%s' does not exist or is not a folder.
     */
    public function __construct(string $root)
    {
        if (isset(self::$instance)) {
            throw new CitrusException('The Citrus Application cannot be initialized twice.');
        }
        self::$instance = $this;

        if (($root = realpath($root)) === false || !is_dir($root)) {
            throw new CitrusException("The passed root path '". func_get_arg(0) ."' does not exist or is not a folder.");
        }
        $this->root = $root;      
        $this->startTime = floatval(microtime(true)); 
        $this->configurator = new Configurator($this);
        $this->container = new Container($this);
        $this->eventManager = new EventManager($this);
    }

    /**
     * Receive Citrus Application Version
     *
     * @return string
     */
    public function getVersion(): string
    {
        return self::VERSION;
    }

    /**
     * Receive used PHP Version
     *
     * @return string
     */
    public function getPHPVersion(): string
    {
        return PHP_VERSION;
    }

    /**
     * Receive assigned Root path
     *
     * @return string
     */
    public function getRoot(): string
    {
        return $this->root;
    }

    /**
     * Receive Applcation Starttime
     *
     * @return float
     */
    public function getStartTime(): float
    {
        return $this->startTime;
    }

    /**
     * Receive Applcation Configurator
     *
     * @return Configurator
     */
    public function getConfigurator(): Configurator
    {
        return $this->configurator;
    }

    /**
     * Receive Applcation Container
     *
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Receive Applcation EventManager
     *
     * @return EventManager
     */
    public function getEventManager(): EventManager
    {
        return $this->eventManager;
    }

    /**
     * Load Environment data.
     * 
     * @param ?string $pathOrFile The path containing the .env file(s), the 
     *                direct filepath or just null to use the root path.
     * @param string $endKey The primary key which determines the general 
     *               environment Citrus is currently in.
     * @return void
     */
    public function loadEnvironment(?string $pathOrFile = null, string $envKey = 'CITRUS_ENV'): void
    {
        if (is_null($pathOrFile)) {
            $pathOrFile = $this->root . DIRECTORY_SEPARATOR . '.env';
        }

        $this->configurator->setContextKey($envKey);
        $this->configurator->setEnvironmentData($_ENV);
        $this->configurator->setEnvironmentData($_SERVER);
        
        if (file_exists($pathOrFile)) {
            $this->configurator->loadEnvironmentFile($pathOrFile, true);
        }
        
        $this->configurator->lockEnvironment();
    }

    /**
     * Get current Environment value.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getEnvironment(string $key, mixed $default = null): mixed
    {
        return $this->configurator->getEnvironment($key, $default);
    }

    /**
     * Get current Application Context.
     *
     * @param string $key
     * @return string
     */
    public function getContext(): string
    {
        return strtolower($this->configurator->getEnvironment(
            $this->configurator->getContextKey(), 'production'
        ));
    }

    /**
     * Check if the current application context is "Production".
     *
     * @return boolean
     */
    public function isProduction()
    {
        $context = $this->getContext();
        return $context === 'production' || $context === 'prod';
    }

    /**
     * Check if the current application context is "Staging".
     *
     * @return boolean
     */
    public function isStaging()
    {
        $context = $this->getContext();
        return $context === 'staging' || $context === 'stage';
    }

    /**
     * Check if the current application context is "Development".
     *
     * @return boolean
     */
    public function isDevelopment()
    {
        $context = $this->getContext();
        return $context === 'development' || $context === 'dev';
    }

    /**
     * Load a configuration file
     *
     * @param string $path A directory path containing multiple configuration 
     *               files or a single file/path.
     * @param ?string $alias An optional alias, used only when the first param
     *                is a direct filepath. 
     * @return void
     * @throws CitrusException The passed configuration (file-) path '%s' does not exist.
     * @throws CitrusException The passed configuration file '%s' is not a valid JSON file. JSON Error: %s
     */
    public function loadConfiguration(string $path, ?string $alias = null): void
    {
        if (($path = realpath($path)) === false) {
            throw new CitrusException("The passed configuration (file-) path '". func_get_arg(0) ."' does not exist.");
        }

        // Loop Directory
        if (is_dir($path)) {
            $handle = opendir($path);
    
            while (($file = readdir($handle)) !== false) {
                if ($file === '.' || $file === '..') {
                    continue;
                }
    
                $filepath = $path . DIRECTORY_SEPARATOR . $file;
                if (!is_file($filepath)) {
                    continue;
                }
                
                $ext = pathinfo($filepath, \PATHINFO_EXTENSION);
                $alias = substr($file, 0, -(strlen($ext)+1));
                $this->loadConfiguration($filepath);
            }
            closedir($handle);
        }
        
        // Load Configuration File
        if (is_file($path)) {
            $ext = pathinfo($path, \PATHINFO_EXTENSION);

            // Get Alias
            if (empty($alias)) {
                $alias = substr(basename($path), 0, -(strlen($ext)+1));
            }

            // Load Configuration depending on file extension
            if ($ext === 'php') {
                $this->configurator->setConfiguration(
                    $alias, PHPParser::parseFile($path)
                );
            } else if ($ext === 'json') {
                $this->configurator->setConfiguration(
                    $alias, JSONParser::parseFile($path)
                );
            } else if ($ext === 'ini' || $ext === 'conf') {
                $this->configurator->setConfiguration(
                    $alias, INIParser::parseFile($path)
                );
            } else if ($ext === 'yaml') {
                $this->configurator->setConfiguration(
                    $alias, YAMLParser::parseFile($path)
                );
            }
        }
    }

    /**
     * Get current Configuration value.
     *
     * @param string $config
     * @param mixed $default
     * @return mixed
     */
    public function getConfiguration(string $config, mixed $default = null): mixed
    {
        return $this->configurator->getConfiguration($config, $default);
    }

    /**
     * Set a core path.
     *
     * @param string $alias
     * @param string $path
     * @return void
     */
    public function setPath(string $alias, string $path): void
    {
        if (array_key_exists($alias, $this->paths)) {
            throw new CitrusException("The passed path alias '$alias' does already exist.");
        }

        if ($alias[0] !== ':') {
            $alias = ':' . $alias;
        }
        $this->paths[$alias] = $this->resolvePath($path);
    }

    /**
     * Set multiple core paths.
     *
     * @param array $paths
     * @return void
     */
    public function setPaths(array $paths)
    {
        array_walk($paths, fn($path, $alias) => $this->setPath($alias, $path));
    }

    /**
     * Resolve a filepath
     *
     * @param string ...$paths
     * @return string
     */
    public function resolvePath(...$paths): string
    {
        if (empty($paths)) {
            return $this->getRoot();
        }
        $DS = DIRECTORY_SEPARATOR;
        $paths = array_map(fn($path) => str_replace(['/', '\\'], $DS, $path), $paths);

        // Resolve Path
        $path = array_shift($paths);

        if ($path[0] === '$') {
            $path = $this->getRoot() . substr($path, 1);
        } else if ($path[0] === ':') {
            $alias = ($index = strpos($path, $DS)) === false? $path: substr($path, 0, $index);
            if(!array_key_exists($alias, $this->paths)) {
                throw new CitrusException("The passed path alias '$alias' does not exist.");
            }
            
            $path = $this->paths[$alias] . ($index > 0? substr($path, $index): '');
        }

        return rtrim(str_replace($DS.$DS, $DS, $path . $DS . implode($DS, $paths)), $DS);
    }

    /**
     * Initialize Container
     *
     * @return void
     */
    public function loadContainer(): void
    {
        $this->container->set(Application::class, $this);
        $this->container->set(Configurator::class, $this->configurator);
        $this->container->set(Container::class, $this->container);
        $this->container->set(EventManager::class, $this->eventManager);
        $this->container->set(Router::class, Router::class);

        $this->container->setAlias('app', Application::class);
        $this->container->setAlias('citrus', Application::class);
        $this->container->setAlias('config', Configurator::class);
        $this->container->setAlias('container', Container::class);
        $this->container->setAlias('events', EventManager::class);
    }

    /**
     * Set single Factory.
     *
     * @param string $alias
     * @param string $class
     * @return void
     */
    public function setFactory(string $alias, string $class): void
    {
        $this->container->setFactory($alias, $class);
    }

    /**
     * Set multiple Factories.
     *
     * @param array $factories
     * @return void
     */
    public function setFactories(array $factories): void
    {
        array_map(
            fn($alias, $class) => $this->container->setFactory($alias, $class),
            array_keys($factories),
            array_values($factories)
        );
    }

    /**
     * Set single Service Provider.
     *
     * @param string $alias
     * @param string $class
     * @return void
     */
    public function setService(string $alias, string $class): void
    {
        $this->container->setFactory($alias, $class);
    }

    /**
     * Set multiple Service Providerss.
     *
     * @param array $services
     * @return void
     */
    public function setServices(array $services): void
    {
        array_map(
            fn($alias, $class) => $this->container->setService($alias, $class),
            array_keys($services),
            array_values($services)
        );
    }

    /**
     * Set multiple Service Providerss.
     *
     * @param string $alias
     * @param string $target
     * @return void
     */
    public function setAlias(string $alias, string $target): void
    {
        $this->container->setAlias($alias, $target);
    }

    /**
     * Set multiple Service Providerss.
     *
     * @param array $aliases
     * @return void
     */
    public function setAliases(array $aliases): void
    {
        array_walk($aliases, fn($target, $alias) => $this->container->setAlias($alias, $target));
    }

    /**
     * Resolve a Class or Class alias.
     *
     * @param string $class
     * @param array $args
     * @return mixed
     */
    public function make(string $class, array $args = []): mixed
    {
        return $this->container->make($class, $args);
    }

    /**
     * Resolve and Call a function or Closure.
     *
     * @param callable|\Closure $function
     * @return void
     */
    public function call(callable | \Closure $function)
    {
        return $this->container->call($function);
    }

    /**
     * Add a new Event Listener.
     *
     * @param string $event The desired event to listen for, or the event class.
     * @param mixed $callback The desired callback function or closure, which 
     *              should be dispatched for this event.
     * @param integer $priority The desired priority of this event.
     * @return void
     */
    public function addListener(string $event, mixed $callback, int $priority = 100): void
    {
        $this->eventManager->addListener($event, $callback, $priority);
    }

    /**
     * Dispatch an Event
     *
     * @param EventContract $event
     * @return EventContract
     */
    public function dispatch(EventContract $event): EventContract
    {
        return $this->eventManager->dispatch($event);
    }

    /**
     * Finalize Citrus Application
     *
     * @param string|null $runtime
     * @return void
     */
    public function finalize(?string $runtime = null)
    {
        if (!empty($runtime)) {
            $instance = $this->make($runtime);
        }

        // Set default timezone
        //@todo
        //date_default_timezone_set();

        // Set Error reporting dependend on the environment
        if ($this->isDevelopment()) {
            error_reporting(-1);
        } else if ($this->isStaging()) {
            error_reporting(E_ERROR | E_WARNING | E_PARSE | E_NOTICE);
        } else {
            error_reporting(0);
        }

        // Dispatch Bootstrap Event
        if ($instance && method_exists($instance, 'bootstrap')) {
            $instance->bootstrap();
        }

        // Dispatch Bootstrap Event
        $this->eventManager->dispatch(
            new ApplicationEvent('bootstrap', [$this])
        );

        // Make Runtime Service
        if ($instance && method_exists($instance, 'finalize')) {
            $instance->finalize();
        }

        // Dispatch Finalize Event
        $this->eventManager->dispatch(
            new ApplicationEvent('finalize', [$this])
        );
    }

    /**
     * Run Application.
     *
     * @param ?Request $request
     * @return void
     */
    public function run(?Request $request = null)
    {
        if (empty($request)) {
            $request = Request::createFromGlobals();
        }

        /** @var Router */
        $router = $this->container->make(Router::class);

        $response = $router->dispatch($request);
        if (!($response instanceof Response)) {
            throw new CitrusException("Received invalid response from route.");
        }
        print($response);
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

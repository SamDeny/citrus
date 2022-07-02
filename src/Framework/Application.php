<?php declare(strict_types=1);

namespace Citrus\Framework;

use Citrus\Console\Console;
use Citrus\Contracts\EventContract;
use Citrus\Events\ApplicationEvent;
use Citrus\Events\RequestEvent;
use Citrus\Http\Request;
use Citrus\Router\Router;
use Citrus\Exceptions\CitrusException;
use Citrus\Http\Response;

/**
 * @method mixed getEnvironment(string $key, mixed $default = null)
 * @method string getContext()
 * @method bool isProduction()
 * @method bool isStaging()
 * @method bool isDevelopment()
 * @method mixed getConfiguration(string $key, mixed $default = null)
 * @method void set(string $alias, mixed $object)
 * @method void setFactory(string $chain, string $class)
 * @method void setFactories(array $factories)
 * @method void setService(string $alias, string $class)
 * @method void setServices(array $services)
 * @method void setAlias(string $alias, string $target)
 * @method void setAliases(array $aliases)
 * @method mixed make(string $class, array $args = [])
 * @method mixed call(mixed $function)
 * @method void addListener(string $event, mixed $callback, int $priority = 100)
 * @method EventContract dispatch(EventContract $event)
 */
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
     * @throws CitrusException The Citrus application has not been initialized yet.
     */
    static public function getInstance(): Application
    {
        if (!self::$instance) {
            throw new CitrusException('The Citrus application has not been initialized yet.');
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
     * Get chained core classes.
     *
     * @param string $name
     * @return void
     * @throws InvalidArgumentException The passed class proeprty '%s' dies not exist.
     */
    public function __get(string $name)
    {
        if (property_exists($this, $name)) {
            return $this->$name;
        } else {
            throw new \InvalidArgumentException("The passed class proeprty '$name' dies not exist.");
        }
    }

    /**
     * Call methods for the chained core classes.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     * @throws BadMethodCallException The passed Application method '%s' does not exist.
     */
    public function __call(string $method, array $arguments = [])
    {
        if (method_exists($this->configurator, $method)) {
            return $this->configurator->{$method}(...$arguments);
        } else if (method_exists($this->container, $method)) {
            return $this->container->{$method}(...$arguments);
        } else if (method_exists($this->eventManager, $method)) {
            return $this->eventManager->{$method}(...$arguments);
        } else {
            throw new \BadMethodCallException("The passed Application method '$method' does not exist.");
        }
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
     * Receive Application Configurator
     *
     * @return Configurator
     */
    public function getConfigurator(): Configurator
    {
        return $this->configurator;
    }

    /**
     * Receive Application Container
     *
     * @return Container
     */
    public function getContainer(): Container
    {
        return $this->container;
    }

    /**
     * Receive Application EventManager
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
        if (($realPath = realpath($path)) === false) {
            throw new CitrusException("The passed configuration (file-) path '$path' does not exist.");
        }
        $path = $realPath;

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

            // Set Configuration
            $this->configurator->setConfiguration(
                $alias,
                $this->configurator->parseConfiguration($path, $ext === 'conf'? 'ini': $ext)
            );
        }
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
     * Check if a path identifier exists
     *
     * @param string $identifier
     * @return bool
     */
    public function hasPath(string $identifier): bool
    {
        return isset($this->paths[$identifier]);
    }

    /**
     * Get all Path identifiers
     *
     * @return array
     */
    public function getPaths(): array
    {
        return $this->paths;
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
     * Load Bootstrap Hook
     * 
     * @param string The desired file to inject.
     * @return void
     */
    public function loadBootstrap(string $filepath)
    {
        if (file_exists($filepath)) {
            require_once $filepath;
        }
    }

    /**
     * Finalize Citrus Application
     *
     * @param string|null $runtime
     * @return void
     */
    public function finalize(?string $runtime = null)
    {
        if ($this->hasPath(':cache') && $this->hasPath(':data')) {
            //$this->loadCaches();
        }

        // Load User Configuration
        $userConfig = $this->resolvePath(':data/config.php');
        if (file_exists($userConfig)) {
            $config = include $userConfig;
            array_walk($config, fn($value, $key) => $this->configurator->setConfiguration($key, $value));
        }

        // Initial Runtime
        if (!empty($runtime)) {
            $instance = $this->make($runtime);
        }

        // Load Caches
        if ($this->hasPath(':cache')) {
/*
            $configCachePool = new MixFileCachePool();

            $configCache = $configCachePool->getItem('config');

            if (!$configCache->isPersistent()) {
                $configCache->push($this->resolvePath(':data/config.php'));
            }
            


            $fileCachePool = new FileCachePool($this->resolvePath(':cache'));
            $this->cacheManager->setCache('crate', $fileCachePool);

            $config = $fileCachePool->getItem('config');

            if ($fileCachePool->hasItem('config') && !$fileCachePool->isExpired('config')) {
                $config = $fileCachePool->getItem('config');
                if (empty ($config)) {
                    $fileCachePool->removeItem('config');
                } else {
                    array_walk($config, fn($data, $alias) => $this->configurator->setConfiguration($alias, $data));
                }
            }
            */
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

        // Dispatch Run Application
        $this->eventManager->dispatch(
            new ApplicationEvent('run', [$this, $request])
        );

        /** @var RequestEvent - Handle Request */
        $event = $this->eventManager->dispatch(
            new RequestEvent([$request])
        );

        // Default Action
        if (!$event->isPrevented()) {
            /** @var Router */
            $router = $this->container->make(Router::class);
    
            $response = $router->dispatch($request);
            if (!($response instanceof Response)) {
                throw new CitrusException("Received invalid response from route.");
            }
            print($response);
        }
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

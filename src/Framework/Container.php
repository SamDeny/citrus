<?php declare(strict_types=1);

namespace Citrus\Framework;

use Citrus\Concerns\FactoryConcern;
use Citrus\Concerns\ServiceConcern;
use Citrus\Contracts\SingletonContract;
use Citrus\Exceptions\CitrusException;
use Ds\Map;
use Ds\Set;
use ReflectionClass;
use ReflectionFunction;

class Container
{

    /**
     * Container Aliases
     *
     * @var Map
     */    
    protected Map $aliases;

    /**
     * Container Factory Storage
     *
     * @var Set
     */    
    protected Set $factories;

    /**
     * Container Service Storage
     *
     * @var Set
     */    
    protected Set $services;

    /**
     * Container Storage
     *
     * @var Map
     */    
    protected Map $storage;

    /**
     * Create a new Application Container
     */
    public function __construct()
    {
        $this->aliases = new Map;
        $this->factories = new Set;
        $this->services = new Set;
        $this->storage = new Map;
    }

    /**
     * Set Container Object.
     *
     * @param string $alias The desired alias to set.
     * @param mixed $object The desired object / value to set.
     * @return void
     */
    public function set(string $alias, mixed $object): void
    {
        $this->storage->put($alias, $object);
    }

    /**
     * Set single Application Factory.
     *
     * @param string $chain
     * @param string $class
     * @return void
     */
    public function setFactory(string $chain, string $class): void
    {
        if (!is_a($class, FactoryConcern::class, true)) {
            throw new CitrusException("The passed factory class '$class' does not extend the Factory concern.");
        }
        $this->aliases->put($chain, $class);
        $this->factories->add($class);
    }

    /**
     * Set multiple Application Factories.
     *
     * @param array $factories
     * @return void
     */
    public function setFactories(array $factories): void
    {
        array_walk($factories, fn($class, $chain) => $this->setFactory($chain, $class));
    }

    /**
     * Set single Application Service Provider.
     *
     * @param string $alias
     * @param string $class
     * @return void
     */
    public function setService(string $alias, string $class): void
    {
        if (!is_a($class, ServiceConcern::class, true)) {
            throw new CitrusException("The passed service provider '$class' does not extend the Service concern.");
        }

        $this->aliases->put($alias, $class);
        $this->services->add($class);
    }

    /**
     * Set multiple Application Services.
     *
     * @param array $services
     * @return void
     */
    public function setServices(array $services): void
    {
        array_walk($services, fn($class, $alias) => $this->setService($alias, $class));
    }

    /**
     * Set single Application Container Alias.
     *
     * @param string $alias
     * @param string $target
     * @return void
     */
    public function setAlias(string $alias, string $target): void
    {
        $this->aliases->put($alias, $target);
    }

    /**
     * Set multiple Application Container Aliases.
     *
     * @param array $aliases
     * @return void
     */
    public function setAliases(array $aliases): void
    {
        array_walk($aliases, fn($target, $alias) => $this->setAlias($alias, $target));
    }

    /**
     * Make or Receive instance.
     *
     * @param string $class
     * @param array $args
     * @return mixed
     */
    public function make(string $class, array $args = []): mixed
    {
        $real = $class;
        while($this->aliases->hasKey($real)) {
            $class = $real;
            $real = $this->aliases[$real];
        }
        if (!class_exists($real)) {
            throw new CitrusException("The passed class name or alias '$class' could not be resolved.");
        }

        // Get Instance
        $instance = null;
        if ($this->storage->hasKey($real)) {
            $instance = $this->storage[$real];
        }

        // Handle
        if ($this->factories->contains($real)) {
            if (!isset($instance)) {
                $instance = $this->resolve($real);
                $this->storage->put($real, $instance);
            }
            return $instance->make($class, ...$args);
        } else if ($this->services->contains($real)) {
            if (!isset($instance)) {
                $instance = $this->resolve($real, $args);
                $instance->bootstrap();
                $this->storage->put($real, $instance);
            }
            return $instance;
        } else {
            if (!$instance || is_string($instance)) {
                $instance = $this->resolve($real, $args);

                if (in_array(SingletonContract::class, class_implements($instance))) {
                    $this->storage->put($real, $instance);
                }
            }
            return $instance;
        }
    }   

    /**
     * Resolve and Call a function.
     *
     * @param callable|\Closure $function
     * @return void
     */
    public function call(callable | \Closure $function): mixed
    {
        $args = [];
        $reflect = new ReflectionFunction($function);

        $params = $reflect->getParameters();
        foreach ($params AS $param) {
            $class = $param->getType()->getName();
            $args[] = $this->make($class);
        }

        return call_user_func_array($function, $args);
    }

    /**
     * Resolve Parameters or Instances.
     *
     * @param string $class
     * @return mixed
     */
    public function resolve(string $class, array $args = []): mixed
    {
        $ref = new ReflectionClass($class);
        $params = [];

        // Resolve
        $constructor = $ref->getConstructor();
        if ($constructor) {
            foreach ($constructor->getParameters() AS $arg) {
                $type = $arg->getType()->getName();
                if (class_exists($type)) {
                    $params[] = $this->make($type);
                } else {
                    break;
                }
            }
        }

        // Return Instance
        return new $class(...[...$params, ...$args]);
    }

}

<?php declare(strict_types=1);

namespace Citrus\Factories;

use Citrus\Exceptions\CitrusException;

class CacheFactory
{

    /**
     * Current CacheManager instance.
     *
     * @var self|null
     */
    static protected ?self $instance = null;

    /**
     * Get current CacheManager instance.
     *
     * @return self
     * @throws CitrusException The CacheManager has not been initialized yet.
     */
    static public function getInstance(): self
    {
        if (!self::$instance) {
            throw new CitrusException('The CacheManager has not been initialized yet.');
        }
        return self::$instance;
    }


    /**
     * Citrus Application
     *
     * @var Application
     */
    protected Application $app;

    /**
     * Root Cache Path
     *
     * @var string|null
     */
    protected ?string $root = null;

    /**
     * Create a new Event Manager
     *
     * @param Application $citrus
     */
    public function __construct(Application $citrus)
    {
        $this->app = $citrus;
    }

    /**
     * Set primary cache path.
     *
     * @param string $path
     * @return void
     */
    public function setCachePath(string $path)
    {
        if (($path = realpath($path) === false) || (is_string($path) && !is_dir($path))) {
            throw new CitrusException("The passed cache path '". func_get_arg(0) ."' does not exist or is not a directory.");
        }

        $this->root = $path;
    }

    /**
     * Get a cached item.
     *
     * @param string $key
     * @return mixed
     */
    public function getItem(string $key): mixed
    {

    }

    /**
     * Check if a cached item exists.
     *
     * @param string $key
     * @return boolean
     */
    public function hasItem(string $key): bool
    {
        
    }

    /**
     * Delete a cached item.
     *
     * @param string $key
     * @return void
     */
    public function deleteItem(string $key): void
    {

    }

    /**
     * Store a cached item.
     *
     * @param string $key
     * @param array $data
     * @return void
     */
    public function storeItem(string $key, array $data = []): void
    {

    }

}

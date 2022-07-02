<?php declare(strict_types=1);

namespace Citrus\Caches\Pools;

use Citrus\Contracts\CacheContract;
use Citrus\Contracts\CachePoolContract;
use Citrus\Exceptions\CitrusException;
use Citrus\FileSystem\Parser\PHPParser;
use Ds\Map;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FileCachePool implements CachePoolContract
{

    /**
     * Assigned Cache Key
     *
     * @var string
     */
    protected string $key;

    /**
     * Assigned Root Cache directory.
     *
     * @var string
     */
    protected string $root;

    /**
     * Created FileCache items
     *
     * @var Map
     */
    protected Map $storage;

    /**
     * Create a new FileCachePool.
     *
     * @param string $key
     * @param string $cachePath
     * @throws CitrusException The passed cache path '%s' does not exist and could not be created.
     * @throws CitrusException The passed cache path '%s' does not point to a directory.
     * @throws CitrusException The passed cache path '%s' is not writable.
     */
    public function __construct(string $key, string $cachePath)
    {
        if (($path = realpath($cachePath) === false)) {
            if (@mkdir($cachePath, 0666, true) === false) {
                throw new CitrusException("The passed cache path '$cachePath' for cache '$key' does not exist and could not be created.");
            }
            $path = realpath($cachePath) . DIRECTORY_SEPARATOR;
        }

        // CachePath must be a directory.
        if (!is_dir($path)) {
            throw new CitrusException("The passed cache path '$cachePath' for cache '$key' does not point to a directory."); 
        }

        // Create Cache Sub-Directory
        $path = $cachePath . $key;
        if (!file_exists($path) && !@mkdir($path, 0666)) {
            throw new CitrusException("The passed cache path '$cachePath.$key' for cache '$key' could not be created.");
        }
        
        // Check If writable
        if (!is_writable($path)) {
            throw new CitrusException("The passed cache path '$cachePath.$key' for cache '$key' is not writable."); 
        }

        // Set Data
        $this->key = $key;
        $this->root = $path . DIRECTORY_SEPARATOR;
        $this->storage = new Map;
    }

    /**
     * Receive assigned CachePool key.
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Receive assigned cache path.
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->root;
    }

    /**
     * Receive a FileCache Item.
     *
     * @param string $key
     * @return FileCache
     */
    public function getItem(string $key): FileCache
    {
        if (strpos($key, '.php') !== strlen($key)-4) {
            $key .= '.php';
        }

        if ($this->storage->hasKey($key)) {
            return $this->storage->get($key);
        } else {
            if (file_exists($this->root . $key)) {
                $data = PHPParser::parseFile($this->root . $key);
            } else {
                $data = [];
            }

            $cacheItem = new FileCache($key, $this->root . $key, $data);
            $this->storage->put($key, $cacheItem);
            return $cacheItem;
        }
    }

    /**
     * Receive multiple FileCache items.
     *
     * @param array $keys
     * @return FileCache[]
     */
    public function getItems(array $keys): array
    {
        $result = [];

        foreach ($keys AS $key) {
            $result[$key] = $this->getItem($key);
        }

        return $result;
    }

    /**
     * Store a single FileCache item.
     *
     * @param CacheContract $item
     * @return boolean
     */
    public function setItem(CacheContract $item): bool
    {
        $key = $item->getKey();
        $value = $item->get();

        if (!is_array($value) && !is_object($value)) {
            return false;
        }

        if (PHPParser::emitFile($value, $this->root . $key)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Receive multiple FileCache items.
     *
     * @param FileCache[] $items
     * @return boolean
     */
    public function setItems(array $items): bool
    {
        array_walk($items, fn($item) => $this->setItem($item));
        return true; 
    }

    /**
     * Delete a single FileCache item.
     *
     * @param string $key
     * @return boolean
     */
    public function deleteItem(string $key): bool
    {
        if (strpos($key, '.php') !== strlen($key)-4) {
            $key .= '.php';
        }

        if (file_exists($this->root . $key)) {
            unlink($this->root . $key);
        }
        
        if ($this->storage->hasKey($key)) {
            $this->storage->remove($key);
        }

        return true;
    }

    /**
     * Delete multiple FileCache items.
     *
     * @param array $key
     * @return boolean
     */
    public function deleteItems(array $keys): bool
    {
        array_walk($keys, fn($key) => $this->deleteItem($key));
        return true;
    }

    /**
     * Clear the whole cache directory.
     *
     * @return boolean
     */
    public function clear(): bool
    {

        // Select Entries
        $entries = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->root, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        // Delete them
        foreach ($entries AS $entry) {
            if ($entry->isDir()) {
                rmdir($entry->getRealPath());
            } else {
                unlink($entry->getRealPath());

            }
        }

        return true;
    }

}

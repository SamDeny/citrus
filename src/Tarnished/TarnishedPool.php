<?php declare(strict_types=1);

namespace Citrus\Tarnished;

use Citrus\Contracts\SingletonContract;
use Citrus\Exceptions\RuntimeException;
use Ds\Map;

class TarnishedPool implements SingletonContract
{

    /**
     * Root Cache Directory.
     *
     * @var string
     */
    protected string $root;

    /**
     * Tarnished Collection Storage
     *
     * @var Map
     */
    protected Map $collections;

    /**
     * Create a new Tarnished Cache Pool.
     *
     * @param string $path
     * @throws RuntimeException The passed cache directory '%s' does not exist and could not be created.
     * @throws RuntimeException The root cache directory '%s' does not exist and could not be created.
     * @throws RuntimeException The root cache directory '%s' is not writable.
     */
    public function __construct(string $path)
    {
        if (($root = realpath($path)) === false) {
            if (@mkdir($path, 0666, true) === false) {
                new RuntimeException("The passed cache directory '". $path ."' does not exist and could not be created.");
            }
            $root = $path;
        }

        $root .= DIRECTORY_SEPARATOR . 'tarnished';
        if (!file_exists($root) && @mkdir($root, 0666) === false) {
            new RuntimeException("The root cache directory '". $root ."' does not exist and could not be created.");
        }

        if (!is_writable($root)) {
            new RuntimeException("The root cache directory '". $root ."' is not writable.");
        }

        $this->root = $root . DIRECTORY_SEPARATOR;
        $this->collections = new Map;
    }

    /**
     * Check if a collection exists.
     *
     * @param string $name
     * @return boolean
     */
    public function hasCollection(string $name)
    {
        if (!str_ends_with($name, '.td')) {
            $name .= '.td';
        }
        return $this->collections->hasKey($name) || file_exists($this->root . $name);
    }

    /**
     * Get or Create a new collection.
     *
     * @param string $name
     * @return void
     */
    public function getCollection(string $name, int $mode = 0x03)
    {
        if (!str_ends_with($name, '.td')) {
            $name .= '.td';
        }

        if ($this->collections->hasKey($name)) {
            return $this->collections->get($name);
        } else {
            $collection = new TarnishedCollection($this->root . $name, $mode);
            $this->collections->put($name, $collection);
            return $collection;
        }
    }





    /**
     * Check if a secure collection exists (does not check if it is already 
     * initialized, use `isSecureCollectionOpen` to check explicitly).
     *
     * @param string $name
     * @return boolean
     */
    public function hasSecureCollection(string $name)
    {
        if (!str_ends_with($name, '.tds')) {
            $name .= '.tds';
        }

        return $this->collections->hasKey($name) || file_exists($this->root . $name);
    }

    /**
     * Check if a secure collection has been opened.
     *
     * @param string $name
     * @return void
     */
    public function isSecureCollectionOpen(string $name)
    {
        if (!str_ends_with($name, '.tds')) {
            $name .= '.tds';
        }

        return $this->collections->hasKey($name);
    }

    /**
     * Open a new secure collection.
     *
     * @param string $name
     * @return void
     * @throws RuntimeException Tried to open secure Tarnished collection '%s' twice.
     */
    public function openSecureCollection(string $name, string $encryptionKey): TarnishedSecureCollection
    {
        if (!str_ends_with($name, '.tds')) {
            $name .= '.tds';
        }

        if ($this->collections->hasKey($name)) {
            throw new RuntimeException("Tried to open secure Tarnished collection '". func_get_arg(0) ."' twice.");
        } else {
            $collection = new TarnishedSecureCollection($this->root . $name, $encryptionKey);
            return $this->collections->put($name, $collection);
            return $collection;
        }
    }

    /**
     * Close an existing secure collection
     *
     * @param string $name
     * @return void
     * @throws RuntimeException Tried to open secure Tarnished collection '%s' twice.
     */
    public function closeSecureCollection(string $name)
    {
        if (!str_ends_with($name, '.tds')) {
            $name .= '.tds';
        }

        if (!$this->collections->hasKey($name)) {
            throw new RuntimeException("Tried to close an unknown secure Tarnished collection '". func_get_arg(0) ."'.");
        } else {
            $this->collections->get($name)->close();
        }
    }

}

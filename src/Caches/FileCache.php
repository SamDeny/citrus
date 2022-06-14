<?php declare(strict_types=1);

namespace Citrus\Caches;

use Citrus\Contracts\CacheContract;

class FileCache implements CacheContract
{

    /**
     * Create a new FileCache item.
     *
     * @param string $key
     * @param string $path
     * @param array $data
     */
    public function __construct(string $key, string $path, array $data = [])
    {
        $this->key = $key;
        $this->path = $path;
        $this->exists = file_exists($path);
        $this->data = $data;
    }

    /**
     * Get Cache Key
     *
     * @return string
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Get cached Data.
     *
     * @return array|object
     */
    public function get(): array|object
    {
        return $this->data;
    }

    /**
     * Set data to cache.
     *
     * @param mixed $data
     * @return static
     */
    public function set(mixed $data): static
    {
        $this->data = $data;
        return $this;
    }

    /**
     * Check if cache is persistent stored.
     *
     * @return boolean
     */
    public function isPersistent(): bool
    {
        return $this->exists;
    }

}

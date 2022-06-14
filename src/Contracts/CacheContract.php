<?php declare(strict_types=1);

namespace Citrus\Contracts;

interface CacheContract
{

    /**
     * Get current cache item key.
     *
     * @return string
     */
    public function getKey(): string;

    /**
     * Get cached value.
     *
     * @return mixed
     */
    public function get(): mixed;

    /**
     * Set value to cache.
     *
     * @param mixed $value
     * @return static
     */
    public function set(mixed $value): static;

    /**
     * Check if cache is persistent stored.
     *
     * @return boolean
     */
    public function isPersistent(): bool;

}

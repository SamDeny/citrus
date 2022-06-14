<?php declare(strict_types=1);

namespace Citrus\Contracts;

interface CachePoolContract
{

    /**
     * Receive assigned CachePool key.
     *
     * @return string
     */
    public function getKey(): string;

    /**
     * Receive single CacheItem.
     *
     * @param string $key
     * @return CacheContract
     */
    public function getItem(string $key): CacheContract;

    /**
     * Receive multiple CacheItems.
     *
     * @param array $key
     * @return CacheContract[]
     */
    public function getItems(array $keys): array;

    /**
     * Store a single CacheItem.
     *
     * @param CacheContract $item
     * @return boolean
     */
    public function setItem(CacheContract $item): bool;

    /**
     * Receive multiple CacheItems.
     *
     * @param CacheContract[] $items
     * @return boolean
     */
    public function setItems(array $items): bool;

    /**
     * Delete single CacheItem.
     *
     * @param string $key
     * @return boolean
     */
    public function deleteItem(string $key): bool;

    /**
     * Delete multiple CacheItems.
     *
     * @param array $key
     * @return boolean
     */
    public function deleteItems(array $keys): bool;

    /**
     * Clear whole CachePool.
     *
     * @return boolean
     */
    public function clear(): bool;

}

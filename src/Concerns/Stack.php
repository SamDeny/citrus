<?php 

namespace Citrus\Concerns;

use ArrayAccess;
use Countable;
use Iterator;
use Serializable;
use Citrus\Exceptions\InvalidStackMethodException;

/**
 * The Citrus Stack class, not to be confused with SPLStack, is a multi-level
 * supporting extension for PHP's default array. This Stack Contract is the 
 * base class and cannot be instantiated directly. While it is not recommended 
 * creating a Stack on your own, without a class abstraction in between, Citrus 
 * still provides the ArrayStack Utility class for such purposes.
 * 
 * Stack provides the following features:
 * 
 * Multi-Path Traversing:
 *      Travel through your data stack using the configured separator. For 
 *      example using key.config.option will be resolved in 
 *      $stack['key']['config']['option']
 *      If this path does not exist, NULL will be returned.
 * 
 * Case-Insensitive Arrays
 *      When enabled, the array can be accessed case-insensitive. However, the 
 *      original key case is preserved and will be returned in a foreach loop 
 *      or when the key method is called.
 *      Keep in mind, that the depending Stack class has to make sure, that the
 *      main stack data array uses unique keys in a case-insensitive way!
 * 
 * Readonly
 *      When enabled, the Stack data array cannot be modified in any way. 
 *      Deleting keys, Changing Values or adding another values will throw an
 *      InvalidStackMethod exception.
 */
abstract class Stack implements ArrayAccess, Countable, Iterator, Serializable
{

    /**
     * Stack Array
     *
     * @var array
     */
    protected array $stack = [];

    /**
     * Travel Separator
     *
     * @var ?string
     */
    protected ?string $separator = '.';

    /**
     * Case Sansitivity
     *
     * @var boolean
     */
    protected bool $caseSensitive = true;

    /**
     * Readonly, disables modification methods.
     *
     * @var boolean
     */
    protected bool $readonly = false;

    /**
     * Current Item Pointer
     *
     * @var integer
     */
    private int $pointer = 0;

    /**
     * Return Source Array
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->stack;
    }

    /**
     * Serializable - Serialize Data
     *
     * @return string|null
     */
    public function serialize(): ?string
    {
        return serialize([
            'data' => $this->data,
            'conf' => [
                $this->separator, 
                $this->caseSensitive, 
                $this->readonly
            ]
        ]);
    }

    /**
     * Serializable - Unserialize Data
     *
     * @return string|null
     */
    public function unserialize(string $data)
    {
        [$data, $conf] = unserialize($data);

        $this->stack = $data;
        $this->separator = $conf[0];
        $this->caseSensitive = $conf[1];
        $this->readonly = $conf[2];
    }

    /**
     * Countable - Get amount of items
     *
     * @return mixed
     */
    public function count(): int 
    {
        return count($this->stack);
    }

    /**
     * Iterator - Get current Item
     *
     * @return mixed
     */
    public function current(): mixed 
    {
        return current($this->stack);
    }

    /**
     * Iterator - Get Current Key
     *
     * @return mixed
     */
    public function key(): mixed
    {
        return key($this->stack);
    }

    /**
     * Iterator - Forward Pointer
     *
     * @return void
     */
    public function next(): void
    {
        next($this->stack);
    }

    /**
     * Iterator - Rewind Pointer
     *
     * @return void
     */
    public function rewind(): void
    {
        rewind($this->stack);
    }

    /**
     * Iterator - Check if items are left.
     *
     * @return boolean
     */
    public function valid(): bool
    {
        return count($this->stack) > $this->pointer;
    }
    
    /**
     * ArrayAccess - Check of offset exists.
     *
     * @param mixed $offset
     * @return boolean
     */
    public function offsetExists(mixed $offset): bool
    {
        var_dump($offset);
        return true;
    }
    
    /**
     * ArrayAccess - Get a value dependong on offset.
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        if (is_null($this->separator)) {
            return $this->stack[$offset] ?? null;
        } else {
            $path = explode($this->separator, $offset);

            $travel = $this->stack;

            foreach($path AS $name) {
                if (array_key_exists($name, $travel)) {
                    $travel = $travel[$name];
                } else {
                    return null;
                }
            }

            return $travel;
        }
    }
    
    /**
     * ArrayAccess - Set a value depending on offset.
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if ($this->readonly) {
            throw new InvalidStackMethodException('The stack has been marked as readonly and cannot be modified.');
        }

        if (is_null($this->separator)) {
            $this->stack[$offset] = $value;
        } else {
            $path = explode($this->separator, $offset);
            $key = array_pop($path);

            $travel = &$this->stack;

            foreach ($path AS $name) {
                if (array_key_exists($name, $travel)) {
                    $travel = &$travel[$name];
                } else {
                    $travel[$name] = [];
                    $travel = &$travel[$name];
                }
            }

            $travel[$key] = $value;
        }
    }
    
    /**
     * ArrayAccess - Delete a value depending on offset.
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        if ($this->readonly) {
            throw new InvalidStackMethodException('The stack has been marked as readonly and cannot be modified.');
        }
    }

}

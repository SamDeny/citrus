<?php declare(strict_types=1);

namespace Citrus\Utilities;

use ArrayAccess;
use Countable;
use Iterator;
use Serializable;

class Dictionary implements ArrayAccess, Countable, Iterator, Serializable
{

    /**
     * Current Dictionary Data.
     *
     * @var array
     */
    protected array $dictionary = [];

    /**
     * Current Dictionary Pointer.
     *
     * @var int
     */
    protected int $pointer = 0;

    /**
     * Create a new Dictionary.
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->dictionary = $data;
    }

    /**
     * Magic serialize class instance.
     *
     * @return array
     */
    public function __serialize(): array
    {
        return $this->data;
    }

    /**
     * Magic unserialize class instance.
     *
     * @param array $data
     * @return void
     */
    public function __unserialize(array $data): void
    {
        $this->dictionary = $data;
        reset($this->dictionary);
    }

    /**
     * Return serialized version of this class instance.
     *
     * @return string
     */
    public function serialize(): string
    {
        return serialize($this->dictionary);
    }

    /**
     * Set class instance from an serialized version.
     *
     * @param string $data
     * @return void
     */
    public function unserialize(string $data): void
    {
        $this->dictionary = unserialize($data);
    }

    /**
     * Basic dictionary representation.
     *
     * @return integer
     */
    public function count(): int
    {
        return count($this->dictionary);
    }

    /**
     * Get current dictionary key-
     *
     * @return mixed
     */
    public function key(): mixed
    {
        return key($this->dictionary);
    }

    /**
     * Get current dictionary value.
     *
     * @return mixed
     */
    public function current(): mixed
    {
        return current($this->dictionary);
    }

    /**
     * Move array pointer forward.
     *
     * @return void
     */
    public function next(): void
    {
        $this->pointer++;
        next($this->dictionary);
    }

    /**
     * Check if pointer is on the end.
     *
     * @return boolean
     */
    public function valid(): bool
    {
        return count($this->dictionary) > $this->pointer;
    }

    /**
     * Reset array pointer.
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->pointer = 0;
        reset($this->dictionary);
    }

    /**
     * Check if item exists.
     *
     * @param mixed $offset
     * @return boolean
     */
    public function offsetExists(mixed $offset): bool
    {
        $path = explode('.', $offset);

        $travel = $this->dictionary;
        foreach($path AS $name) {
            if (!array_key_exists($name, $travel)) {
                return false;
            } else {
                $travel = $travel[$name];
            }
        }
        return true;
    }

    /**
     * Get a dictionary item.
     *
     * @param mixed $offset
     * @return mixed
     */
    public function offsetGet(mixed $offset): mixed
    {
        $path = explode('.', $offset);

        $travel = $this->dictionary;
        foreach($path AS $name) {
            if (!array_key_exists($name, $travel)) {
                return null;
            } else {
                $travel = $travel[$name];
            }
        }
        return $travel;
    }

    /**
     * Set a dictionary item.
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $path = explode('.', $offset);
        $key = array_pop($path);

        $travel = &$this->dictionary;
        foreach($path AS $name) {
            if (array_key_exists($name, $travel)) {
                $travel = &$travel[$name];
            } else {
                $travel[$name] = [];
                $travel = &$travel[$name];
            }
        }
        $travel[$key] = $value;
    }
    
    /**
     * Unset a dictionary item.
     *
     * @param mixed $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        $path = explode('.', $offset);
        $key = array_pop($path);
        
        $travel = &$this->dictionary;
        foreach($path AS $name) {
            if (array_key_exists($name, $travel)) {
                $travel = &$travel[$name];
            } else {
                return;
            }
        }

        if (array_key_exists($key, $travel)) {
            unset($travel[$key]);
        }
    }

    /**
     * Recursively merged the data array
     *
     * @param array $data
     * @return void
     */
    public function merge(array $data): void
    {
        $this->dictionary = array_merge_recursive(
            $this->dictionary,
            $data
        );
    }

    /**
     * Return dictionary array.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->dictionary;
    }

    /**
     * Return dictionary array as JSON,
     *
     * @return string
     */
    public function toJson(): string
    {
        return json_encode($this->dictionary);
    }

}

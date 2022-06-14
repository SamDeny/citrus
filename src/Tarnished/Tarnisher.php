<?php declare(strict_types=1);

namespace Citrus\Tarnished;

use Ds\Set;

class Tarnisher
{

    /**
     * Tarnisher observed Sources
     *
     * @var Set
     */
    public Set $sources;
    
    /**
     * Tarnisher collected data
     *
     * @var mixed
     */
    public mixed $data = null;
    
    /**
     * Tarnisher nullable
     *
     * @var bool
     */
    public bool $nullable = false;

    /**
     * Create a new Tarnisher
     */
    public function __construct()
    {
        $this->sources = new Set;
    }

    /**
     * Observe File
     *
     * @param string $filepath
     * @return static
     */
    public function observe(string $filepath): self
    {
        $this->sources->add($filepath);
        return $this;
    }

    /**
     * Collect data
     *
     * @param mixed $data
     * @return mixed
     */
    public function collect(mixed $data): mixed
    {
        $this->data = $data;
        return $data;
    }

    /**
     * Mark as nullable - Ignore cached data.
     *
     * @param boolean $state
     * @return static
     */
    public function nullable(bool $state): self
    {
        $this->nullable = $state;
        return $this;
    }

}

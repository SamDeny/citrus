<?php declare(strict_types=1);

namespace Citrus\Concerns;

use Citrus\Contracts\MultitonContract;
use Citrus\Framework\Application;

abstract class FactoryConcern implements MultitonContract
{  

    /**
     * Current Application isntance.
     *
     * @var Application
     */
    protected Application $application;

    /**
     * Create a new [Service] Provider.
     *
     * @param Application $citrus
     */
    public function __construct(Application $citrus)
    {
        echo '-';
        $this->application = $citrus;
    }

    /**
     * Make or Return the already instantiated class.
     *
     * @return mixed
     */
    abstract public function make(string $class, ...$arguments);

}

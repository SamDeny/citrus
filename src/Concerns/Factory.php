<?php declare(strict_types=1);

namespace Citrus\Concerns;

use Citrus\Framework\Application;
use Citrus\Contracts\MultitonInterface;

abstract class Factory implements MultitonInterface
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

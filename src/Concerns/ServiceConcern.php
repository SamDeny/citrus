<?php declare(strict_types=1);

namespace Citrus\Concerns;

use Citrus\Contracts\SingletonContract;
use Citrus\Framework\Application;

abstract class ServiceConcern implements SingletonContract
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
        $this->application = $citrus;
    }

    /**
     * Bootstrap your Service Provider when initialized
     *
     * @return void
     */
    public function bootstrap()
    {
        // Nothing to do here.
    }

}

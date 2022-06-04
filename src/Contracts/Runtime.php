<?php declare(strict_types=1);

namespace Citrus\Contracts;

use Citrus\Framework\Application;

interface Runtime
{

    /**
     * Contstructor
     *
     * @param Application $application
     */
    public function __construct(Application $application);

    /**
     * Basic Initialization Method
     *
     * @return void
     */
    public function init(): void;

    /**
     * Basic Finish Method
     *
     * @return void
     */
    public function finish(): void;

}

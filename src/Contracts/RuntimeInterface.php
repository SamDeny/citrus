<?php declare(strict_types=1);

namespace Citrus\Contracts;

use Citrus\Framework\Application;

interface RuntimeInterface
{

    /**
     * Contstructor
     *
     * @param Application $application
     */
    public function __construct(Application $application);

    /**
     * Basic Bootstrap method
     *
     * @return void
     */
    public function bootstrap(): void;

    /**
     * Before Finish Method
     *
     * @return void
     */
    public function beforeFinish(): void;

    /**
     * After Finish Method
     *
     * @return void
     */
    public function afterFinish(): void;

}

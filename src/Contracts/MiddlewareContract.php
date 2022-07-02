<?php declare(strict_types=1);

namespace Citrus\Contracts;

use Citrus\Http\Request;
use Citrus\Http\Response;

interface MiddlewareContract
{

    /**
     * Process Request
     *
     * @param Request $request
     * @param \Closure $next
     * @return Response
     */
    public function process(Request $request, \Closure $next): Response;

}

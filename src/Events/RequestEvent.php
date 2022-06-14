<?php declare(strict_types=1);

namespace Citrus\Events;

use Citrus\Events\Orders\PreventDefaultOrder;
use Citrus\Events\Orders\UniqueOrder;

class RequestEvent extends GenericEvent
{
    use PreventDefaultOrder;
    use UniqueOrder;

}

<?php declare(strict_types=1);

namespace Citrus\Events;

use Citrus\Events\Orders\NamespaceOrder;
use Citrus\Events\Orders\UniqueOrder;

class ApplicationEvent extends GenericEvent
{
    use NamespaceOrder;
    use UniqueOrder;

}

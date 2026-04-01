<?php

namespace App\Logging;

use Illuminate\Log\Logger as IlluminateLogger;
use Monolog\Formatter\JsonFormatter;

class CustomizeObservabilityLogger
{
    public function __invoke(IlluminateLogger $logger): void
    {
        $formatter = new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true);
        $monolog = $logger->getLogger();

        foreach ($monolog->getHandlers() as $handler) {
            if (method_exists($handler, 'setFormatter')) {
                $handler->setFormatter($formatter);
            }
        }
    }
}

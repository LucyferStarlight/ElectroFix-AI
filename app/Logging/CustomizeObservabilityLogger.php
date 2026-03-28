<?php

namespace App\Logging;

use Monolog\Formatter\JsonFormatter;
use Monolog\Logger;

class CustomizeObservabilityLogger
{
    public function __invoke(Logger $logger): void
    {
        $formatter = new JsonFormatter(JsonFormatter::BATCH_MODE_JSON, true);

        foreach ($logger->getHandlers() as $handler) {
            if (method_exists($handler, 'setFormatter')) {
                $handler->setFormatter($formatter);
            }
        }
    }
}

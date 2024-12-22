<?php

namespace Bmatovu\HttpLogger\Support;

use Illuminate\Log\Logger;
use Monolog\Formatter\LineFormatter;

/**
 * @see https://betterstack.com/community/guides/logging/how-to-start-logging-with-laravel
 */
class LogFormatter
{
    public const FORMAT = "[%datetime%] [%context.request_id%] %channel%.%level_name%: %message% %context% %extra%\n";

    public function __invoke(Logger $logger)
    {
        $includeStacktraces = config('http-logger.stacktrace', true);

        foreach ($logger->getHandlers() as $handler) {
            $formatter = new LineFormatter(
                $format = self::FORMAT,
                $dateFormat = 'Y-m-d H:i:s', // 'Y-m-d\TH:i:sP'
                $allowInlineLineBreaks = true,
                $ignoreEmptyContextAndExtra = true,
                $includeStacktraces
            );

            $handler->setFormatter($formatter);
        }
    }
}

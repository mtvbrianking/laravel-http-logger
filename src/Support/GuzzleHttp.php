<?php

namespace Bmatovu\HttpLogger\Support;

use GuzzleHttp\HandlerStack;
use GuzzleHttp\MessageFormatter;
use GuzzleHttp\Middleware;
use Illuminate\Container\Container;
use Illuminate\Support\Str;

class GuzzleHttp
{
    /**
     * @see https://github.com/guzzle/guzzle/blob/master/src/MessageFormatter.php
     * @see https://github.com/gmponos/guzzle-log-middleware
     */
    public static function LogMiddleware(HandlerStack $handlerStack): HandlerStack
    {
        $id = Str::random(10);

        $messageFormats = [
            'info' => [
                "[{$id}] HTTP_OUT [Request] {method} {target} HTTP/{version}",
                "[{$id}] HTTP_OUT [Response] HTTP/{version} {code} {phrase}",
            ],
            'debug' => [
                "[{$id}] HTTP_OUT [Request] {req_headers}\n{req_body}",
                "[{$id}] HTTP_OUT [Response] {res_headers}\n{res_body}",
            ],
        ];

        $logger = Container::getInstance()->get('log');

        $level = 'debug'; // config('log.level', 'info') == 'debug' ? 'debug' : 'info';

        collect($messageFormats[$level])->each(static function ($format) use ($logger, $level, $handlerStack) {
            $messageFormatter = new MessageFormatter($format);

            $logMiddleware = Middleware::log($logger, $messageFormatter, $level);

            $handlerStack->unshift($logMiddleware);
        });

        return $handlerStack;
    }
}

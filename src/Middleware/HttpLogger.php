<?php

namespace Bmatovu\HttpLogger\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\HeaderBag;
use Symfony\Component\HttpFoundation\Response;

class HttpLogger
{
    /**
     * @see \Symfony\Component\HttpFoundation\HeaderBag::__toString()
     */
    protected function __toJson(HeaderBag $headerBag, array $masked = [], array $hidden = []): string
    {
        if (! $headers = $headerBag->all()) {
            return '';
        }

        $_headers = [];
        foreach ($headers as $name => $values) {
            $name = ucwords($name, '-');

            if (\in_array($name, $hidden, true)) {
                continue;
            }

            if (\in_array($name, $masked, true)) {
                $_headers[$name] = Str::limit(implode(', ', $values), 15, '**********');

                continue;
            }

            $_headers[$name] = implode(', ', $values);
        }

        ksort($_headers);

        return json_encode($_headers, JSON_NUMERIC_CHECK); // JSON_PRETTY_PRINT
    }

    public function handle(Request $request, \Closure $next): mixed
    {
        $this->logRequest($request);

        $response = $next($request);

        $response?->headers->set('X-Request-Id', $this->getRequestId());

        return $response;
    }

    public function terminate(Request $request, Response $response): void
    {
        $duration = microtime(true) - LARAVEL_START;

        $this->logResponse($response, $duration);
    }

    public function logRequest(Request $request): void
    {
        $protocolVersion = $request->getProtocolVersion();

        $method = strtoupper($request->getMethod());

        $uri = $request->getPathInfo();

        Log::info("HTTP_IN [Request] {$method} {$uri} {$protocolVersion}");
        Log::debug(\sprintf(
            "HTTP_IN [Request] %s\r\n%s",
            $this->__toJson($request->headers, ['Authorization'], ['Postman-Token', 'Host']),
            $request->getContent()
        ));
    }

    public function logResponse(Response $response, float $duration): void
    {
        $statusCode = $response->getStatusCode();

        $version = $response->getProtocolVersion();

        $statusText = Response::$statusTexts[$statusCode];

        $duration = (int) ($duration * 1000);

        Log::info("HTTP_IN [Response] HTTP/{$version} {$statusCode} {$statusText} {$duration}ms");
        Log::debug(\sprintf("HTTP_IN [Response] %s\r\n%s", $this->__toJson($response->headers), $response->getContent()));
    }

    protected function getRequestId(): string
    {
        return $_SERVER['REQUEST_ID'] ?? '';
    }
}

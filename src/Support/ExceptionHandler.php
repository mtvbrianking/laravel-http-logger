<?php

namespace Bmatovu\HttpLogger\Support;

use Throwable;
use Illuminate\Support\Facades\Log;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Auth\Access\AuthorizationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;

class ExceptionHandler
{
    public static function prepareApiResponse(Request $request, Throwable $th): Response
    {
        $context = method_exists($th, 'getContext') ? $th->getContext() : [];

        Log::error(sprintf("%s: %s in %s:%d", get_class($th), $th->getMessage(), $th->getFile(), $th->getLine()), $context);

        if ($th instanceof AuthenticationException) {
            return response(['message' => $th->getMessage()], 401);
        }

        if ($th instanceof AuthorizationException) {
            return response(['message' => $th->getMessage()], $th->status() ?? 403);
        }

        if ($th instanceof ModelNotFoundException) {
            $model = explode('\\', $th->getModel());

            $message = end($model);

            if (count($th->getIds()) > 0) {
                $message .= ' [ID: ' . implode(', ', $th->getIds()) . ']';
            }

            $message .= ' not found.';

            return response(['message' => $message], 404);
        }

        if ($th instanceof NotFoundHttpException) {
            return response(['message' => $th->getMessage()], 404);
        }

        if ($th instanceof ValidationException) {
            return response(['message' => $th->getMessage(), 'errors' => $th->errors()], $th->status);
        }

        if ($th instanceof HttpException) {
            return response(['message' => $th->getMessage()], $th->getStatusCode());
        }

        if ($th instanceof QueryException) {
            return response(['message' => $th->getPrevious()->getMessage()], 500);
        }

        if (method_exists($th, 'render')) {
            return $th->render($request);
        }

        $fqcn = explode('\\', get_class($th));
        $name = end($fqcn);

        return response(['message' => $th->getMessage() ?: $name], 500);
    }
}

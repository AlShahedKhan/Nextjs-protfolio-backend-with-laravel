<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        //
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (MethodNotAllowedHttpException $exception, Request $request) {
            if (! $request->expectsJson() && ! $request->is('api/*')) {
                return null;
            }

            $allowHeader = (string) ($exception->getHeaders()['Allow'] ?? '');
            $allowedMethods = array_values(array_filter(array_map(
                static fn (string $method): string => strtoupper(trim($method)),
                explode(',', $allowHeader),
            )));

            $correctMethod = collect($allowedMethods)
                ->first(static fn (string $method): bool => $method !== 'HEAD')
                ?? $allowedMethods[0]
                ?? null;

            $message = $correctMethod
                ? sprintf(
                    'The %s method is not supported for this route. Use %s.',
                    strtoupper($request->method()),
                    $correctMethod,
                )
                : sprintf(
                    'The %s method is not supported for this route.',
                    strtoupper($request->method()),
                );
// 
            return response()->json([
                'message' => $message,
                'wrong_method' => strtoupper($request->method()),
                'correct_method' => $correctMethod,
                'allowed_methods' => $allowedMethods,
            ], 405, $allowHeader !== '' ? ['Allow' => $allowHeader] : []);
        });
    })->create();

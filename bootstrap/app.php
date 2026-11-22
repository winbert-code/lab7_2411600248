<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Lab 5 behavior: a missing record (or any random element appended to a
        // link) should not dead-end at a 404. Return to the page the visitor was
        // on, or to the dashboard when there is no safe internal referrer.
        $exceptions->render(function (NotFoundHttpException $exception, Request $request) {
            // API and AJAX callers keep their normal JSON 404 response.
            if ($request->expectsJson() || $request->is('api/*')) {
                return null;
            }

            // Only a real Referer header counts as the "current page". When a
            // URL is typed directly the browser sends none, so go to dashboard
            // (which sends guests on to login).
            $previous = $request->header('referer');
            $base = rtrim(url('/'), '/');

            if (! is_string($previous) || $previous === '' || $previous === $request->fullUrl()) {
                return redirect()->route('dashboard');
            }

            if ($previous !== $base && ! str_starts_with($previous, $base.'/')) {
                return redirect()->route('dashboard');
            }

            $path = parse_url($previous, PHP_URL_PATH) ?: '';

            if (preg_match('#/{2,}|\.{2,}#', $path)) {
                return redirect()->route('dashboard');
            }

            return redirect()->to($previous);
        });
    })->create();

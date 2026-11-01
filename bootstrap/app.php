<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'finance.access' => \App\Http\Middleware\EnsureFinanceAccess::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (HttpException $e, Request $request) {
            if ($e->getStatusCode() === 419) {
                if ($request->expectsJson()) {
                    return response()->json([
                        'message' => 'Sesi Anda telah berakhir. Silakan login kembali.',
                        'redirect' => route('login'),
                    ], 419);
                }

                return redirect()->route('login')
                    ->with('status', 'Sesi Anda telah berakhir. Silakan login kembali.')
                    ->with('warning', 'Sesi Anda telah berakhir. Silakan login kembali.');
            }
        });
    })->create();

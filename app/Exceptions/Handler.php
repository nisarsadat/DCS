<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (Throwable $e, $request) {
            if (! app()->environment('production') || ! $request->is('api/*')) {
                return null;
            }

            $message = preg_replace('/postgres(?:ql)?:\/\/\S+/i', 'postgres://redacted', $e->getMessage()) ?? '';
            $message = preg_replace('/(password|pwd)=([^\s&]+)/i', '$1=redacted', $message) ?? $message;
            $databaseUrl = trim((string) (getenv('DATABASE_URL') ?: ''), " \t\n\r\0\x0B\"'");

            return response()->json([
                'error' => class_basename($e),
                'message' => substr($message, 0, 240),
                'postgres' => str_starts_with($databaseUrl, 'postgres'),
                'neon' => str_contains($databaseUrl, 'neon.tech'),
                'length' => strlen($databaseUrl),
            ], 500);
        });
    }
}

<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
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
    }

    public function render($request, Throwable $e)
    {
        if ($e instanceof ModelNotFoundException) {
            return response()->json(['error' => 'Resource not found.'], 404);
        }

        if ($e instanceof AuthorizationException) {
            return response()->json(['error' => $e->getMessage()], 403);
        }

        if ($e instanceof ProjectCreationException){
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }

        if ($e instanceof ProjectUpdateException){
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }

        if ($e instanceof TaskCreationException){
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }
        if ($e instanceof TaskUpdateException){
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }

        if ($e instanceof SubTaskCreationException){
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }

        if ($e instanceof SubTaskUpdateException){
            return response()->json(['error' => $e->getMessage()], $e->getCode());
        }

        if ($e instanceof HttpExceptionInterface) {
            return response()->json([
                'error' => $e->getMessage(),
            ], $e->getStatusCode());
        }

        if ($e instanceof AuthenticationException){
            return response()->json(['error' => $e->getMessage()], 401);
        }

        // Fallback for unhandled exceptions
        return response()->json([
            'error' => 'Internal Server Error',
            'message' => $e->getMessage(),
        ], 500);
    }
}

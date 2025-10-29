<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    public function register(): void
    {
        $this->renderable(function (ModelNotFoundException $e, $request) {
            return response()->json(['error' => 'Пользователь не найден'], 404);
        });

        $this->renderable(function (InsufficientFundsException $e, $request) {
            return response()->json(['error' => $e->getMessage()], 409);
        });
    }
}

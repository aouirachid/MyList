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

        //
        $this->reportable(function (ModelNotFoundException $e, $request) {
            return response()->json([
                'status' => 404,
                'message' => 'The resource you request does not exist or has been moved', 
            ], Response::HTTP_NOT_FOUND);
        });

        // $this->reportable(function (AuthenticationException $e, $request) {
        //     return response()->json([
        //         'status' => 401,
        //         'message' => '',
        //     ], Response:: );
        // });

        $this->reportable(function (AuthorizationException $e, $request) {
            return response()->json([
                'status' => 403,
                'message' => '',
            ], Response::HTTP_UNAUTHORIZED);
        });

        $this->reportable(function (ValidationException $e, $request) {
            return response()->json([
                'status' => 422,
                'message' => '',
                'errors' => $e->errors(),
            ], Response::HTTP_UPROCESSABLE_ENTITY);
        });
    }
}

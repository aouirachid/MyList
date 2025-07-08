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

        //this one handle not found error
        $this->reportable(function (ModelNotFoundException $e, $request) {
            return response()->json([
                'status' => 404,
                'message' => 'The resource you request does not exist or has been moved', 
            ], Response::HTTP_NOT_FOUND);
        });

        //this one handle unAuthenticationed user
         $this->reportable(function (AuthenticationException $e, $request) {
             return response()->json([
                'status' => Response::HTTP_UNAUTHORIZED,
                'message' => 'Unauthenticated. Please log in to access this resource.',
            ], Response::HTTP_UNAUTHORIZED);
         });

        //this one handle UNAUTHORIZED user
        $this->reportable(function (AuthorizationException $e, $request) {
            return response()->json([
                'status' => Response::HTTP_FORBIDDEN,
                'message' => 'You do not have permission to access this resource.',
            ], Response::HTTP_FORBIDDEN);
        });

        //this one handle invalide data
        $this->reportable(function (ValidationException $e, $request) {
            return response()->json([
                'status' => 422,
                'message' => 'You have entred unvalidated data',
                'errors' => $e->errors(),
            ], Response::HTTP_UPROCESSABLE_ENTITY);
        });
    }
}

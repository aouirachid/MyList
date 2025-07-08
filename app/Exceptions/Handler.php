<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
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
        $this->renderable(function (ModelNotFoundException $e, $request) {
            if ($request->expectsJson()) {
            return response()->json([
                    'status' => Response::HTTP_NOT_FOUND,
                'message' => 'The resource you request does not exist or has been moved', 
            ], Response::HTTP_NOT_FOUND);
            }
        });

        //this one handle unAuthenticationed user
        $this->renderable(function (AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                'status' => Response::HTTP_UNAUTHORIZED,
                'message' => 'Unauthenticated. Please log in to access this resource.',
            ], Response::HTTP_UNAUTHORIZED);
            }
         });

        //this one handle UNAUTHORIZED user
        $this->renderable(function (AuthorizationException $e, $request) {
            if ($request->expectsJson()) {
            return response()->json([
                'status' => Response::HTTP_FORBIDDEN,
                'message' => 'You do not have permission to access this resource.',
            ], Response::HTTP_FORBIDDEN);
            }
        });

        //this one handle invalide data
        $this->renderable(function (ValidationException $e, $request) {
            if ($request->expectsJson()) {
            return response()->json([
                    'status' => Response::HTTP_UNPROCESSABLE_ENTITY,
                'message' => 'You have entred unvalidated data',
                'errors' => $e->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }
        });

        //this one handle internal server error
        $this->renderable(function (Throwable $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'status' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'message' => 'Internal server error',
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        });
    }
}

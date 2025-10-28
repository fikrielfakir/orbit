<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Laravel\Passport\Exceptions\OAuthServerException;
use League\OAuth2\Server\Exception\OAuthServerException as LeagueOAuthServerException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
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
        // Handle OAuth authentication errors
        $this->renderable(function (OAuthServerException|LeagueOAuthServerException $e, $request) {
            return response()->json([
                'error' => 'invalid_credentials',
                'error_description' => 'The provided credentials are incorrect',
                'message' => 'Authentication failed. Please check your credentials.',
            ], 401);
        });

        // Other exception handlers...
        $this->reportable(function (Throwable $e) {
            // Report exceptions to your error tracking service
        });
    }
}
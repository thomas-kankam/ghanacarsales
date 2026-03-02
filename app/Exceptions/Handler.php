<?php
namespace App\Exceptions;

use Exception;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use TypeError;

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
        $this->renderable(function (QueryException $e, Request $request) {
            Log::info($e);
            // Log::channel("query_exception")->error($e);

            return response()->json([
                "data" => [
                    "status_code"   => "500",
                    "message"       => "Action Unsuccessful",
                    "in_error"      => true,
                    "reason"        => "A system error 01 occured", //$e->getMessage(),
                    "data"          => [],
                    "point_in_time" => now(),
                ],
            ], 200);
        });

        $this->renderable(function (TypeError $e, Request $request) {
            Log::info($e);
            // Log::channel("type_error")->error($e);

            return response()->json([
                "data" => [
                    "status_code"   => "422",
                    "message"       => "Action Unsuccessful",
                    "in_error"      => true,
                    "reason"        => "A system error 02 occured",
                    "data"          => [],
                    "point_in_time" => now(),
                ],
            ], 200);
        });

        $this->renderable(function (NotFoundHttpException $e, Request $request) {
            Log::info($e);

            return response()->json([
                "data" => [
                    "status_code"   => "404",
                    "message"       => "Action Unsuccessful",
                    "in_error"      => true,
                    "reason"        => $e->getMessage(), //"A System error 03 occured.",
                    "data"          => [],
                    "point_in_time" => now(),
                ],
            ], 200);
        });

        $this->renderable(function (ThrottleRequestsException $e, Request $request) {
            Log::info($e);
            $key       = 'limiter:' . ($request->user()?->id ?? $request->ip());
            $remaining = RateLimiter::remaining($key, 60);

            // Log::channel("throttle")->error($e);

            return response()->json([
                "data" => [
                    "status_code"   => "500",
                    "message"       => "Action Unsuccessful",
                    "in_error"      => true,
                    "reason"        => "Too many request from your ip/user. Try again in " . $remaining . " seconds",
                    "data"          => [],
                    "point_in_time" => now(),
                ],
            ], 200);
        });

        // validation error
        $this->renderable(function (ValidationException $e, Request $request) {
            Log::info($e);
            return response()->json([
                "data" => [
                    "status_code"   => "422",
                    "message"       => "Validation Error",
                    "in_error"      => true,
                    "reason"        => "The given data was invalid",
                    "errors"        => $e->errors(),
                    "point_in_time" => now(),
                ],
            ], 200);
        });

        // authentication error
        $this->renderable(function (AuthenticationException $e, Request $request) {
            Log::info($e);
            return response()->json([
                "data" => [
                    "status_code"   => "401",
                    "message"       => "AuthenticationException Error",
                    "in_error"      => true,
                    "reason"        => "The given data was invalid",
                    "errors"        => $e->getMessage(),
                    "data"          => [],
                    "point_in_time" => now(),
                ],
            ], 200);
        });

        $this->renderable(function (Exception $e, Request $request) {
            Log::info($e);
            return response()->json([
                "data" => [
                    "status_code"   => "422",
                    "message"       => "Action Unsuccessful",
                    "in_error"      => true,
                    "reason"        => "System error",
                    "data"          => [],
                    "point_in_time" => now(),
                ],
            ], 200);
        });
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

abstract class BaseApiController extends Controller
{
    public const API_SUCCESS = 200;
    public const API_CREATED = 201;
    public const API_ACCEPTED = 202;
    public const API_NO_CONTENT = 204;
    public const API_BAD_REQUEST = 400;
    public const API_UNAUTHORIZED = 401;
    public const API_FORBIDDEN = 403;
    public const API_NOT_FOUND = 404;
    public const API_VALIDATION_ERROR = 422;
    public const API_FAIL = 500;

    /**
     * Send standardized API response
     */
    protected function apiResponse(
        bool $in_error = false,
        string $message = "",
        int $status_code = self::API_SUCCESS,
        mixed $data = null,
        ?string $reason = null
    ): JsonResponse {
        $response = [
            'success' => !$in_error,
            'message' => $message,
            'status_code' => $status_code,
        ];

        if ($data !== null) {
            $response['data'] = $data;
        }

        if ($reason !== null) {
            $response['reason'] = $reason;
        }

        return response()->json($response, $status_code);
    }

    /**
     * Send email notification
     */
    protected static function sendEmail(
        string $email,
        string $email_class,
        array $parameters = []
    ): void {
        try {
            \Mail::to($email)->send(new $email_class(...$parameters));
        } catch (\Exception $e) {
            \Log::error("Failed to send email to {$email}: " . $e->getMessage());
        }
    }
}

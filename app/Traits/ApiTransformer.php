<?php
namespace App\Traits;

use Illuminate\Http\JsonResponse;

trait ApiTransformer
{
    /**
     * Send standardized API response
     */
    protected static function apiResponse(bool $in_error = false, string $message = "", string $status_code, mixed $data = null, ?string $reason = null): JsonResponse
    {
        return response()->json([
            "data" => [
                "status_code"   => $status_code,
                "message"       => $message,
                "in_error"      => $in_error,
                "reason"        => $reason,
                "data"          => $data,
                "point_in_time" => now(),
            ],
        ], $status_code);
    }
}

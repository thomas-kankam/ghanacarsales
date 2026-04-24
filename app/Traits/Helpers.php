<?php
namespace App\Traits;

use App\Models\Actor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait Helpers
{
    protected static function apiToken(Actor $actor, string $tokenName = 'Laravel Password Grant Client'): Actor
    {
        $tokenResult = $actor->createToken($tokenName);

        $actor->token = $tokenResult->accessToken;

        $expiresAt = $tokenResult->token->expires_at;
        // Omit expiry in API when TTL is long-lived so clients treat the token as non-expiring.
        $longLivedThreshold = now()->copy()->addYears(50);
        if ($expiresAt !== null && $expiresAt->lessThanOrEqualTo($longLivedThreshold)) {
            $actor->token_expires_at = $expiresAt->toIso8601String();
            $actor->expires_in       = max(0, $expiresAt->getTimestamp() - now()->getTimestamp());
        } else {
            $actor->token_expires_at = null;
            $actor->expires_in       = null;
        }

        return $actor;
    }

    protected static function base64ImageDecode(?string $base64_image): ?string
    {
        if ($base64_image === null || trim($base64_image) === '') {
            return null;
        }

        $base64_image = trim($base64_image);

        if (str_starts_with($base64_image, 'http://') || str_starts_with($base64_image, 'https://')) {
            return $base64_image;
        }

        if (preg_match('/^data:image\/(\w+);base64,/', $base64_image, $matches)) {
            $image_extension = $matches[1];
            $image_data      = substr($base64_image, strpos($base64_image, ',') + 1);

            $fileName  = Str::random(15) . '.' . $image_extension;
            $file_path = "uploads/cars/" . $fileName;

            Storage::disk("public")->put($file_path, base64_decode($image_data));
            return config("custom.urls.backend_url")  . "/storage/" . $file_path;
        }

        return null;
    }

    protected static function deleteImage(?string $image_path): bool
    {
        if (! $image_path) {
            return false;
        }

        try {
            $path = parse_url($image_path, PHP_URL_PATH);

            if (! $path) {
                return false;
            }

            // Remove /public if present
            $path = str_replace('/public', '', $path);

            // Remove /storage/
            $path = str_replace('/storage/', '', $path);

            // Remove leading slash
            $path = ltrim($path, '/');

            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->delete($path);
            }

            return false;
        } catch (\Exception $e) {
            logger()->error('Failed to delete image', [
                'error' => $e->getMessage(),
                'path'  => $image_path,
            ]);
            return false;
        }
    }

    protected static function decodeJsonArray(mixed $value): ?array
    {
        if (! is_string($value)) {
            return null;
        }
        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE && is_array($decoded) ? $decoded : null;
    }

    protected static function maskEmail($email): string
    {
        $parts      = explode('@', $email);
        $name       = $parts[0];
        $maskedName = substr($name, 0, 2) . str_repeat('*', max(0, strlen($name) - 4)) . substr($name, -2);
        return $maskedName . '@' . $parts[1];
    }

    protected static function maskMobile($mobile): string
    {
        return substr($mobile, 0, 3) . '****' . substr($mobile, -3);
    }
}

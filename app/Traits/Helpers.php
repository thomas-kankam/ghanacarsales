<?php
namespace App\Traits;

use App\Models\Actor;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

trait Helpers
{
    protected static function apiToken(Actor $actor): Actor
    {
        $accessToken  = $actor->createToken('Laravel Password Grant Client')->accessToken;
        $actor->token = $accessToken;

        return $actor;
    }

    protected static function googleAuthCheck(string $access_token, array $payload)
    {
        $url = "https://www.googleapis.com/oauth2/v3/userinfo?access_token={$access_token}";

        $response = Http::get($url);

        if ($response->successful()) {
            $userData = $response->json();

            if ($userData['email'] !== $payload['email'] || $userData['sub'] !== $payload['provider_id']) {
                return null;
            }
            return $userData;
        }

        return null;
    }

    protected static function base64ImageDecode(string $base64_image)
    {
        if (preg_match('/^data:image\/(\w+);base64,/', $base64_image, $matches)) {
            $image_extension = $matches[1];
            $image_data      = substr($base64_image, strpos($base64_image, ',') + 1);

            $fileName  = Str::random(15) . '.' . $image_extension;
            $file_path = "uploads/images/" . $fileName;

            Storage::disk("public")->put($file_path, base64_decode($image_data));
            return config("custom.urls.backend_url") . "/" . "storage/" . $file_path;
        }
    }

    protected static function deleteImage(?string $image_path): bool
    {
        if (! $image_path) {
            return false;
        }

        try {
            // Extract just the file path from the full URL if it's a URL
            $path = parse_url($image_path, PHP_URL_PATH);
            if ($path) {
                $path = str_replace('/storage/', '', $path);
            } else {
                $path = $image_path;
            }

            if (Storage::disk('public')->exists($path)) {
                return Storage::disk('public')->delete($path);
            }
            return false;
        } catch (\Exception $e) {
            logger()->error('Failed to delete image', ['error' => $e->getMessage(), 'path' => $image_path]);
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

    protected static function getAuthorizationToken(): string
    {
        $token       = "";
        $auth_header = request()->header('Authorization');

        if ($auth_header) {
            if (str_starts_with($auth_header, 'Bearer ')) {
                $token = substr($auth_header, 7);
            }
        }
        return $token;
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

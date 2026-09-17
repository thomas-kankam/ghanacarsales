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
            $image_extension = strtolower($matches[1]);
            if ($image_extension === 'jpeg') {
                $image_extension = 'jpg';
            }

            $image_data = base64_decode(substr($base64_image, strpos($base64_image, ',') + 1), true);
            if ($image_data === false || $image_data === '') {
                return null;
            }

            return static::storeImageBinary($image_data, $image_extension);
        }

        return null;
    }

    /**
     * Store an uploaded multipart image file and return its public URL.
     * Prefer this over base64 JSON — ModSecurity treats file parts differently.
     */
    protected static function storeUploadedImage(\Illuminate\Http\UploadedFile $file): ?string
    {
        if (! $file->isValid()) {
            return null;
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }

        $binary = file_get_contents($file->getRealPath());
        if ($binary === false || $binary === '') {
            return null;
        }

        return static::storeImageBinary($binary, $extension);
    }

    protected static function storeImageBinary(string $binary, string $extension): string
    {
        $binary = static::compressImageBinary($binary, $extension);

        $fileName  = Str::random(15) . '.' . $extension;
        $file_path = "uploads/cars/" . $fileName;

        Storage::disk("public")->put($file_path, $binary);

        return rtrim((string) config("custom.urls.backend_url"), '/') . "/storage/" . $file_path;
    }

    /**
     * Normalize multipart file or existing public URL to a stored public URL.
     * Base64 data-URIs are not accepted.
     */
    protected static function normalizeCarImage(mixed $img): ?string
    {
        if ($img instanceof \Illuminate\Http\UploadedFile) {
            return static::storeUploadedImage($img);
        }

        if (! is_string($img) || trim($img) === '') {
            return null;
        }

        $img = trim($img);

        if (str_starts_with($img, 'data:')) {
            return null;
        }

        if (str_starts_with($img, 'http://') || str_starts_with($img, 'https://')) {
            return $img;
        }

        return null;
    }

    /**
     * Resize large photos and re-encode JPGs so stored files stay reasonable.
     */
    protected static function compressImageBinary(string $binary, string &$extension): string
    {
        if (! function_exists('imagecreatefromstring')) {
            return $binary;
        }

        $image = @imagecreatefromstring($binary);
        if ($image === false) {
            return $binary;
        }

        $width  = imagesx($image);
        $height = imagesy($image);
        $maxDim = 1920;

        if ($width > $maxDim || $height > $maxDim) {
            $scale = min($maxDim / $width, $maxDim / $height);
            $newW  = max(1, (int) round($width * $scale));
            $newH  = max(1, (int) round($height * $scale));
            $resized = imagecreatetruecolor($newW, $newH);

            if (in_array($extension, ['png', 'webp'], true)) {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }

            imagecopyresampled($resized, $image, 0, 0, 0, 0, $newW, $newH, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        ob_start();
        if ($extension === 'png') {
            imagepng($image, null, 6);
        } elseif ($extension === 'webp' && function_exists('imagewebp')) {
            imagewebp($image, null, 80);
        } else {
            // Normalize camera photos (jpg/jpeg and unknowns) to jpg
            $extension = 'jpg';
            imagejpeg($image, null, 82);
        }
        imagedestroy($image);

        $compressed = ob_get_clean();

        return $compressed !== false && $compressed !== '' ? $compressed : $binary;
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

<?php

namespace App\Support;

use App\Services\NeonStorage;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class Media
{
    public static function disk(): string
    {
        $disk = (string) config('filesystems.media_disk', 'public');

        return $disk === 'neon' ? 'neon' : ($disk !== '' ? $disk : 'public');
    }

    public static function put(UploadedFile $file, string $directory): string
    {
        self::assertAllowed($file);

        if (self::disk() === 'neon') {
            return app(NeonStorage::class)->put($file, trim($directory, '/'));
        }

        return $file->store(trim($directory, '/'), self::disk());
    }

    public static function delete(?string $path): void
    {
        if ($path === null || $path === '' || str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return;
        }

        if (self::disk() === 'neon') {
            app(NeonStorage::class)->delete($path);

            return;
        }

        Storage::disk(self::disk())->delete($path);
    }

    public static function url(?string $path): ?string
    {
        if ($path === null || $path === '') {
            return null;
        }

        if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
            return $path;
        }

        if (self::disk() === 'neon') {
            return app(NeonStorage::class)->temporaryUrl($path);
        }

        return asset('storage/'.ltrim($path, '/'));
    }

    public static function assertAllowed(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf', 'doc', 'docx'];
        if (! in_array($extension, $allowed, true)) {
            throw ValidationException::withMessages([
                'file' => 'Upload a JPG, PNG, WEBP, GIF, PDF, or Word document.',
            ]);
        }

        $mime = strtolower((string) $file->getMimeType());
        $allowedMimes = [
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/octet-stream',
        ];
        if ($mime !== '' && ! in_array($mime, $allowedMimes, true) && ! str_starts_with($mime, 'image/')) {
            throw ValidationException::withMessages([
                'file' => 'This file type is not allowed.',
            ]);
        }
    }
}

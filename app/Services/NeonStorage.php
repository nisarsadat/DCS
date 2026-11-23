<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

class NeonStorage
{
    public function put(UploadedFile $file, string $directory): string
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'bin');
        $key = trim($directory, '/').'/'.Str::uuid()->toString().'.'.$extension;
        $mime = $file->getMimeType() ?: 'application/octet-stream';
        $signed = $this->presign($key, 'upload', $mime);

        $response = Http::withHeaders($signed['headers'])
            ->withBody(file_get_contents($file->getRealPath()), $mime)
            ->timeout(60)
            ->send($signed['method'], $signed['url']);

        if (! $response->successful()) {
            throw new RuntimeException('Neon storage upload failed ('.$response->status().').');
        }

        return $key;
    }

    public function delete(string $key): void
    {
        $response = Http::withToken($this->apiKey())
            ->acceptJson()
            ->timeout(30)
            ->delete($this->objectUrl($key));

        if (! $response->successful() && $response->status() !== 404) {
            throw new RuntimeException('Neon storage delete failed ('.$response->status().').');
        }

        Cache::forget($this->cacheKey($key));
    }

    public function temporaryUrl(string $key): ?string
    {
        $ttl = max(60, min(604800, ((int) config('filesystems.media_url_ttl', 60)) * 60));

        return Cache::remember($this->cacheKey($key), max(30, $ttl - 30), function () use ($key, $ttl) {
            $signed = $this->presign($key, 'download', null, $ttl);

            return $signed['url'];
        });
    }

    public function ensurePrivateBucket(): void
    {
        $name = $this->bucket();
        $existing = $this->listBuckets();
        foreach ($existing as $bucket) {
            if (($bucket['name'] ?? null) === $name) {
                return;
            }
        }

        $response = Http::withToken($this->apiKey())
            ->acceptJson()
            ->timeout(30)
            ->post($this->bucketCollectionUrl(), [
                'name' => $name,
                'access_level' => 'private',
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Neon bucket create failed ('.$response->status().').');
        }
    }

    private function presign(string $key, string $operation, ?string $contentType = null, ?int $expires = null): array
    {
        $payload = ['operation' => $operation];
        if ($contentType) {
            $payload['content_type'] = $contentType;
        }
        if ($expires) {
            $payload['expires_in_seconds'] = $expires;
        }

        $response = Http::withToken($this->apiKey())
            ->acceptJson()
            ->timeout(30)
            ->post($this->objectUrl($key).'/presign', $payload);

        if (! $response->successful()) {
            throw new RuntimeException('Neon storage presign failed ('.$response->status().').');
        }

        $data = $response->json();
        if (! is_array($data) || empty($data['url']) || empty($data['method'])) {
            throw new RuntimeException('Neon storage presign response was incomplete.');
        }

        return [
            'url' => $data['url'],
            'method' => $data['method'],
            'headers' => is_array($data['headers'] ?? null) ? $data['headers'] : [],
        ];
    }

    private function listBuckets(): array
    {
        $response = Http::withToken($this->apiKey())
            ->acceptJson()
            ->timeout(30)
            ->get($this->bucketCollectionUrl());

        if (! $response->successful()) {
            throw new RuntimeException('Neon bucket list failed ('.$response->status().').');
        }

        $buckets = $response->json('buckets');

        return is_array($buckets) ? $buckets : [];
    }

    private function bucketCollectionUrl(): string
    {
        return $this->base().'/buckets';
    }

    private function objectUrl(string $key): string
    {
        return $this->base().'/buckets/'.rawurlencode($this->bucket()).'/objects/'.rawurlencode($key);
    }

    private function base(): string
    {
        $base = rtrim((string) config('services.neon.api_base'), '/');
        $project = (string) config('services.neon.project_id');
        $branch = (string) config('services.neon.branch_id');
        if ($project === '' || $branch === '') {
            throw new RuntimeException('Neon project and branch are not configured.');
        }

        return $base.'/projects/'.rawurlencode($project).'/branches/'.rawurlencode($branch);
    }

    private function bucket(): string
    {
        $bucket = (string) config('services.neon.bucket', 'assets');

        return $bucket !== '' ? $bucket : 'assets';
    }

    private function apiKey(): string
    {
        $key = (string) config('services.neon.api_key');
        if ($key === '') {
            throw new RuntimeException('NEON_API_KEY is not configured.');
        }

        return $key;
    }

    private function cacheKey(string $key): string
    {
        return 'neon-media:'.sha1($key);
    }
}

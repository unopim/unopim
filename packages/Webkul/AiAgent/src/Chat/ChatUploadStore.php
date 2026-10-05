<?php

namespace Webkul\AiAgent\Chat;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Keeps chat uploads attached to their conversation so a follow-up turn
 * ("Yes, proceed") still sees the image or file sent earlier in it.
 *
 * Uploads live in a per-admin, per-conversation directory rather than the
 * session: admin pages poll in the background, and with a lock-free session
 * driver a poll that started mid-turn writes a stale upload list back.
 */
class ChatUploadStore
{
    /**
     * How long an upload stays attached to the conversation, in seconds.
     */
    protected const TTL = 600;

    protected const DISK = 'public';

    /**
     * Store newly uploaded images, or return the ones already attached to this conversation.
     *
     * @param  array<int, UploadedFile>  $uploads
     * @return array<int, string>
     */
    public function images(array $uploads, ?string $conversationId): array
    {
        return $this->attach('images', $uploads, $conversationId, fn (UploadedFile $image, string $directory): string|false => $image->store($directory, self::DISK));
    }

    /**
     * Store newly uploaded files, or return the ones already attached to this conversation.
     *
     * Files keep their original extension because PHP often reports CSV uploads
     * as text/plain, so hashName() would store them as .txt and ImportProducts
     * would reject them.
     *
     * @param  array<int, UploadedFile>  $uploads
     * @return array<int, string>
     */
    public function files(array $uploads, ?string $conversationId): array
    {
        return $this->attach('files', $uploads, $conversationId, function (UploadedFile $file, string $directory): string|false {
            $extension = strtolower($file->getClientOriginalExtension());

            return $file->storeAs($directory, Str::random(40).($extension !== '' ? '.'.$extension : ''), self::DISK);
        });
    }

    /**
     * Detach images the provider refused so later turns of the conversation do not resend them.
     *
     * @param  array<int, string>  $paths
     */
    public function forgetImages(array $paths): void
    {
        File::delete($paths);
    }

    /**
     * @param  array<int, UploadedFile>  $uploads
     * @param  callable(UploadedFile, string): (string|false)  $store
     * @return array<int, string>
     */
    protected function attach(string $kind, array $uploads, ?string $conversationId, callable $store): array
    {
        $disk = Storage::disk(self::DISK);
        $directory = $this->directory($kind, $conversationId);

        if ($uploads !== []) {
            if ($conversationId !== null) {
                $disk->deleteDirectory($directory);
            }

            return array_map(fn (UploadedFile $upload): string => $disk->path((string) $store($upload, $directory)), $uploads);
        }

        if ($conversationId === null) {
            return [];
        }

        $freshSince = now()->timestamp - self::TTL;

        return collect($disk->files($directory))
            ->filter(fn (string $path): bool => $disk->lastModified($path) >= $freshSince)
            ->map(fn (string $path): string => $disk->path($path))
            ->values()
            ->all();
    }

    protected function directory(string $kind, ?string $conversationId): string
    {
        if ($conversationId === null) {
            return "ai-agent/{$kind}";
        }

        return "ai-agent/{$kind}/".auth()->guard('admin')->id().'/'.sha1($conversationId);
    }
}

<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class MediaService
{
    private const DISK = 'public';

    /**
     * Upload an image.
     */
    public function upload(
        UploadedFile $file,
        string $directory
    ): string {
        $path = $file->store($directory, self::DISK);

        if ($path === false) {
            throw new RuntimeException('Image upload failed.');
        }

        return $path;
    }

    /**
     * Generate a public image URL.
     */
    public function url(?string $path): ?string
    {
        if (!$path) {
            return null;
        }

        return Storage::disk(self::DISK)->url($path);
    }

    /**
     * Delete an image from storage.
     */
    public function delete(?string $path): bool
    {
        if (!$path) {
            return true;
        }

        $disk = Storage::disk(self::DISK);

        if (!$disk->exists($path)) {
            return true;
        }

        return $disk->delete($path);
    }

    /**
     * Replace an existing image with a new image.
     */
    public function replace(
        UploadedFile $file,
        string $directory,
        ?string $oldPath = null
    ): string {
        $newPath = $this->upload($file, $directory);

        if ($oldPath && ! $this->delete($oldPath)) {
            $this->delete($newPath);

            throw new RuntimeException(
                'Could not remove the previous image.'
            );
        }

        return $newPath;
    }
}

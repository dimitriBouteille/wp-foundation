<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Image;

/**
 * Deletes the resized versions of an image when it is deleted or when its sizes are regenerated,
 * hooked by {@see ImageServiceProvider}.
 */
final class GeneratedImagesCleaner
{
    public function onDelete(int $attachmentId): void
    {
        $this->delete($attachmentId);
    }

    /**
     * @param array<string, mixed> $metadata
     * @return array<string, mixed>
     */
    public function onRegenerate(array $metadata, int $attachmentId): array
    {
        $this->delete($attachmentId, $metadata);

        return $metadata;
    }

    /**
     * @param array<string, mixed> $metadata The new metadata, when the sizes are regenerated
     */
    private function delete(int $attachmentId, array $metadata = []): void
    {
        if (!wp_attachment_is_image($attachmentId)) {
            return;
        }

        $path = get_attached_file($attachmentId);
        if (!is_string($path) || $path === '') {
            return;
        }

        $currentMetadata = wp_get_attachment_metadata($attachmentId);
        $files = [
            ...$this->getFiles($path, is_array($currentMetadata) ? $currentMetadata : []),
            ...$this->getFiles($path, $metadata),
        ];

        (new ImageResizer())->deleteGeneratedFiles($path, array_values(array_unique($files)));
    }

    /**
     * Absolute paths of the WordPress sizes and of the original image (before WordPress scaled it down).
     *
     * @param array<string, mixed> $metadata
     * @return string[]
     */
    private function getFiles(string $path, array $metadata): array
    {
        $directory = dirname($path);
        $files = [];
        $sizes = is_array($metadata['sizes'] ?? null) ? $metadata['sizes'] : [];
        foreach ($sizes as $size) {
            if (is_array($size) && is_string($size['file'] ?? null) && $size['file'] !== '') {
                $files[] = $directory . '/' . $size['file'];
            }
        }

        $original = $metadata['original_image'] ?? null;
        if (is_string($original) && $original !== '') {
            $files[] = $directory . '/' . $original;
        }

        return $files;
    }
}

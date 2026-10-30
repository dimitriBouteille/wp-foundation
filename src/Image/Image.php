<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Image;

/**
 * Attachment of the media library.
 *
 * Same accessors as Timber\Image for the ones used by the templates (`image.src`, `image.src('large')`, `image.alt`),
 * so that the templates do not change when migrating.
 */
class Image implements \Stringable
{
    /**
     * @var array<int|string, mixed>|null
     */
    private ?array $metadata = null;

    final protected function __construct(
        public readonly int $id,
        protected readonly \WP_Post $post,
    ) {}

    #[\Override]
    public function __toString(): string
    {
        return $this->src();
    }

    public static function fromId(int|string|null $id): ?static
    {
        if (!is_numeric($id) || (int) $id <= 0) {
            return null;
        }

        $post = get_post((int) $id);
        if (!$post instanceof \WP_Post || $post->post_type !== 'attachment') {
            return null;
        }

        return new static($post->ID, $post);
    }

    /**
     * @param mixed $value Attachment ID, URL, WP_Post or ACF field value (whatever its return format)
     */
    public static function fromValue(mixed $value): ?static
    {
        return match (true) {
            $value instanceof self => static::fromId($value->id),
            $value instanceof \WP_Post => static::fromId($value->ID),
            is_array($value) => static::fromValue($value['ID'] ?? $value['id'] ?? null),
            is_numeric($value) => static::fromId((int) $value),
            is_string($value) && $value !== '' => static::fromId(attachment_url_to_postid($value)),
            default => null,
        };
    }

    /**
     * @param string|null $size Registered image size, null for the original file (always the original file for the SVG and the non images)
     */
    public function src(?string $size = null): string
    {
        if ($size === null || $size === 'full' || $this->isSvg() || !$this->isImage()) {
            return (string) wp_get_attachment_url($this->id);
        }

        return (string) wp_get_attachment_image_url($this->id, $size);
    }

    public function srcset(string $size = 'full'): string
    {
        return (string) wp_get_attachment_image_srcset($this->id, $size);
    }

    public function alt(): string
    {
        $alt = $this->meta('_wp_attachment_image_alt');

        return is_string($alt) ? trim(strip_tags($alt)) : '';
    }

    public function caption(): string
    {
        return trim((string) wp_get_attachment_caption($this->id));
    }

    public function title(): string
    {
        return get_the_title($this->post);
    }

    public function mimeType(): string
    {
        return (string) $this->post->post_mime_type;
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mimeType(), 'image/');
    }

    public function isVideo(): bool
    {
        return str_starts_with($this->mimeType(), 'video/');
    }

    public function isSvg(): bool
    {
        return $this->mimeType() === 'image/svg+xml';
    }

    public function width(?string $size = null): ?int
    {
        return $this->dimension('width', $size);
    }

    public function height(?string $size = null): ?int
    {
        return $this->dimension('height', $size);
    }

    /**
     * Absolute path of the original file, null when it does not exist.
     */
    public function path(): ?string
    {
        $path = get_attached_file($this->id);

        return is_string($path) && is_file($path) ? $path : null;
    }

    public function meta(string $key): mixed
    {
        return get_post_meta($this->id, $key, true);
    }

    private function dimension(string $name, ?string $size): ?int
    {
        $metadata = $this->metadata ??= (array) wp_get_attachment_metadata($this->id);
        if ($size !== null && $size !== 'full') {
            $sizes = $metadata['sizes'] ?? null;
            $metadata = is_array($sizes) && is_array($sizes[$size] ?? null) ? $sizes[$size] : [];
        }

        $value = $metadata[$name] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }
}

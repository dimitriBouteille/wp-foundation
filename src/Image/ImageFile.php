<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Image;

/**
 * Image located in the uploads or in the content directory (theme, plugins), with its path and its URL.
 */
final readonly class ImageFile
{
    public function __construct(
        public string $directoryPath,
        public string $directoryUrl,
        public string $filename,
        public string $extension,
    ) {}

    /**
     * @param string $src Absolute or relative (to the home URL) URL, or absolute path of the image.
     */
    public static function fromSource(string $src): ?self
    {
        $uploads = wp_upload_dir();
        $bases = [];
        if ($uploads['error'] === false) {
            $bases[] = [$uploads['basedir'], $uploads['baseurl']];
        }

        // @phpstan-ignore constant.notFound
        $bases[] = [WP_CONTENT_DIR, content_url()];

        $isUrl = !str_starts_with($src, '/') || str_starts_with($src, '//');
        $isAbsoluteUrl = str_contains($src, '://') || str_starts_with($src, '//');
        $source = $src;
        if (!$isUrl && !self::isInside($src, array_column($bases, 0))) {
            $isUrl = true;
        }

        if ($isUrl && !$isAbsoluteUrl) {
            $source = untrailingslashit(network_home_url()) . '/' . ltrim($src, '/');
        }

        foreach ($bases as [$basePath, $baseUrl]) {
            $base = $isUrl ? $baseUrl : $basePath;
            if (!self::startsWith($source, $base)) {
                continue;
            }

            $relative = substr(self::withoutScheme($source), strlen(self::withoutScheme($base)));
            $relative = (string) strtok($relative, '?#');
            $info = pathinfo($relative);
            $subDirectory = in_array($info['dirname'] ?? '', ['/', '.', ''], true) ? '' : $info['dirname'];
            $directoryUrl = untrailingslashit($baseUrl) . $subDirectory;
            if ($isUrl && !$isAbsoluteUrl) {
                $directoryUrl = str_replace(home_url(), '', $directoryUrl);
            }

            return new self(
                directoryPath: untrailingslashit($basePath) . $subDirectory,
                directoryUrl: $directoryUrl,
                filename: $info['filename'],
                extension: strtolower($info['extension'] ?? ''),
            );
        }

        return null;
    }

    public function basename(): string
    {
        return $this->extension !== '' ? $this->filename . '.' . $this->extension : $this->filename;
    }

    public function path(): string
    {
        return $this->directoryPath . '/' . $this->basename();
    }

    public function url(): string
    {
        return $this->directoryUrl . '/' . $this->basename();
    }

    public function withBasename(string $filename, string $extension): self
    {
        return new self($this->directoryPath, $this->directoryUrl, $filename, $extension);
    }

    public function isSvg(): bool
    {
        if ($this->extension === 'svg') {
            return true;
        }

        return is_file($this->path()) && mime_content_type($this->path()) === 'image/svg+xml';
    }

    /**
     * @param string[] $directories
     */
    private static function isInside(string $path, array $directories): bool
    {
        foreach ($directories as $directory) {
            if (str_starts_with($path, $directory)) {
                return true;
            }
        }

        return false;
    }

    private static function startsWith(string $value, string $prefix): bool
    {
        return str_starts_with(self::withoutScheme($value), self::withoutScheme($prefix));
    }

    private static function withoutScheme(string $url): string
    {
        return (string) preg_replace('#^https?:#i', '', $url);
    }
}

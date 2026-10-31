<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\Image;

use Brain\Monkey\Functions;
use Dbout\WpFoundation\Image\GeneratedImagesCleaner;
use Dbout\WpFoundation\Image\ImageResizer;
use Dbout\WpFoundation\Image\ImageServiceProvider;
use Dbout\WpFoundation\Tests\TestCase;
use PHPUnit\Framework\Attributes\RequiresFunction;

final class ImageResizerTest extends TestCase
{
    private string $uploads;

    protected function setUp(): void
    {
        parent::setUp();

        $this->uploads = $this->createTemporaryDirectory();
        Functions\when('wp_upload_dir')->justReturn(['basedir' => $this->uploads, 'baseurl' => 'https://example.com/uploads', 'error' => false]);
        Functions\when('content_url')->justReturn('https://example.com/app');
        Functions\when('home_url')->justReturn('https://example.com');
        Functions\when('network_home_url')->justReturn('https://example.com');
        Functions\when('untrailingslashit')->alias(fn (string $value): string => rtrim($value, '/\\'));
    }

    public function testReturnsTheSourceWhenTheImageCannotBeResized(): void
    {
        $resizer = new ImageResizer();

        self::assertSame('', $resizer->resize(null, 100));
        self::assertSame('', $resizer->resize('', 100));
        self::assertSame('/photo.jpg', $resizer->resize('/photo.jpg', 'large'));
        self::assertSame('/photo.jpg', $resizer->resize('/photo.jpg', 0, 0));
        self::assertSame('https://cdn.example.org/photo.jpg', $resizer->resize('https://cdn.example.org/photo.jpg', 100));
        self::assertSame('https://example.com/uploads/missing.jpg', $resizer->resize('https://example.com/uploads/missing.jpg', 100));
    }

    public function testReturnsTheResizedImageAlreadyGenerated(): void
    {
        touch($this->uploads . '/photo.jpg', time() - 60);
        touch($this->uploads . '/photo-300x200-c-default.jpg');
        touch($this->uploads . '/photo-300x0-c-f.jpg');
        Functions\expect('wp_get_image_editor')->never();

        $resizer = new ImageResizer();

        self::assertSame('https://example.com/uploads/photo-300x200-c-default.jpg', $resizer->resize('https://example.com/uploads/photo.jpg', 300, 200, 'unknown'));
        self::assertSame('https://example.com/uploads/photo-300x0-c-f.jpg', $resizer->resize('https://example.com/uploads/photo.jpg', 300, null, false));
    }

    #[RequiresFunction('imagewebp')]
    public function testConvertsTheImageToWebp(): void
    {
        $image = imagecreatetruecolor(10, 10);
        imagepng($image, $this->uploads . '/photo.png');
        Functions\when('wp_check_filetype')->justReturn(['ext' => 'png', 'type' => 'image/png']);

        $url = (new ImageResizer())->toWebp('https://example.com/uploads/photo.png');

        self::assertSame('https://example.com/uploads/photo.webp', $url);
        self::assertSame('image/webp', mime_content_type($this->uploads . '/photo.webp'));
    }

    public function testDeletesTheGeneratedFiles(): void
    {
        $files = ['photo.jpg', 'photo.webp', 'photo-300x200-c-default.jpg', 'photo-300x200-c-top-center.webp', 'photo-large.jpg', 'photo-150x150.jpg', 'photo-150x150.webp', 'other-300x200-c-default.jpg'];
        foreach ($files as $file) {
            touch($this->uploads . '/' . $file);
        }

        (new ImageResizer())->deleteGeneratedFiles($this->uploads . '/photo.jpg', [$this->uploads . '/photo-150x150.jpg']);

        self::assertSame(['other-300x200-c-default.jpg', 'photo-150x150.jpg', 'photo-large.jpg', 'photo.jpg'], $this->listFiles());
    }

    public function testTheCleanerDeletesTheGeneratedFilesOfTheAttachment(): void
    {
        foreach (['photo.jpg', 'photo-300x200-c-default.jpg', 'photo-150x150.webp', 'photo-scaled.webp'] as $file) {
            touch($this->uploads . '/' . $file);
        }

        Functions\when('wp_attachment_is_image')->justReturn(true);
        Functions\when('get_attached_file')->justReturn($this->uploads . '/photo.jpg');
        Functions\when('wp_get_attachment_metadata')->justReturn([
            'sizes' => ['thumbnail' => ['file' => 'photo-150x150.jpg']],
            'original_image' => 'photo-scaled.jpg',
        ]);

        $metadata = ['sizes' => []];
        self::assertSame($metadata, (new GeneratedImagesCleaner())->onRegenerate($metadata, 12));
        self::assertSame(['photo.jpg'], $this->listFiles());
    }

    public function testTheCleanerIgnoresTheOtherAttachments(): void
    {
        touch($this->uploads . '/photo-300x200-c-default.jpg');
        Functions\when('wp_attachment_is_image')->justReturn(false);

        (new GeneratedImagesCleaner())->onDelete(12);

        self::assertSame(['photo-300x200-c-default.jpg'], $this->listFiles());
    }

    public function testTheProviderRegistersTheCleaner(): void
    {
        (new ImageServiceProvider())->boot();

        self::assertNotFalse(has_action('delete_attachment', 'Dbout\WpFoundation\Image\GeneratedImagesCleaner->onDelete()'));
        self::assertNotFalse(has_filter('wp_generate_attachment_metadata', 'Dbout\WpFoundation\Image\GeneratedImagesCleaner->onRegenerate()'));
    }

    /**
     * @return string[]
     */
    private function listFiles(): array
    {
        $files = scandir($this->uploads);
        self::assertIsArray($files);
        $files = array_values(array_diff($files, ['.', '..']));
        sort($files);

        return $files;
    }
}

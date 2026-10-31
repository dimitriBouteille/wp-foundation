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
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
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
        Functions\when('wp_check_filetype')->alias(fn (string $file): array => match (strtolower(pathinfo($file, PATHINFO_EXTENSION))) {
            'jpg' => ['ext' => 'jpg', 'type' => 'image/jpeg'],
            'png' => ['ext' => 'png', 'type' => 'image/png'],
            'webp' => ['ext' => 'webp', 'type' => 'image/webp'],
            default => ['ext' => false, 'type' => false],
        });
        Functions\when('wp_get_image_editor_output_format')->justReturn([]);
        Functions\when('wp_image_editor_supports')->justReturn(true);
        Functions\when('wp_get_default_extension_for_mime_type')->alias(fn (string $mime): string => ['image/jpeg' => 'jpg', 'image/webp' => 'webp', 'image/avif' => 'avif'][$mime] ?? '');
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

    /**
     * @return iterable<string, array{int, int, int, int, array{int, int, int, int, int, int}}>
     */
    public static function tinySizeProvider(): iterable
    {
        yield 'height rounded to 0' => [3000, 500, 2, 0, [1000, 0, 1000, 500, 2, 1]];
        yield 'width rounded to 0' => [500, 3000, 0, 2, [0, 333, 500, 1000, 1, 2]];
    }

    /**
     * @param array{int, int, int, int, int, int} $crop
     */
    #[DataProvider('tinySizeProvider')]
    public function testComputesASizeOfAtLeastOnePixel(int $sourceWidth, int $sourceHeight, int $width, int $height, array $crop): void
    {
        touch($this->uploads . '/photo.jpg');
        $editor = \Mockery::mock();
        $editor->shouldReceive('get_size')->andReturn(['width' => $sourceWidth, 'height' => $sourceHeight]);
        $editor->shouldReceive('crop')->once()->with(...$crop);
        $editor->shouldReceive('set_quality');
        $editor->shouldReceive('save')->once()->andReturnUsing(fn (string $path): array => ['path' => $path]);
        Functions\when('wp_get_image_editor')->justReturn($editor);
        Functions\when('is_wp_error')->justReturn(false);

        $url = (new ImageResizer())->resize('https://example.com/uploads/photo.jpg', $width, $height);

        self::assertSame(sprintf('https://example.com/uploads/photo-%dx%d-c-default.jpg', $width, $height), $url);
    }

    public function testNamesTheResizedImageAfterTheOutputFormat(): void
    {
        // e.g. add_filter('image_editor_output_format', fn () => ['image/jpeg' => 'image/webp'])
        touch($this->uploads . '/photo.jpg');
        Functions\when('wp_get_image_editor_output_format')->justReturn(['image/jpeg' => 'image/webp']);
        $target = $this->uploads . '/photo-300x200-c-default.jpg.webp';
        $editor = $this->mockEditor();
        $editor->shouldReceive('save')->once()->with($target)->andReturnUsing(function (string $path): array {
            touch($path);

            return ['path' => $path, 'file' => basename($path), 'mime-type' => 'image/webp'];
        });

        $resizer = new ImageResizer();
        $generated = $resizer->resize('https://example.com/uploads/photo.jpg', 300, 200);
        // Generated once: the file of the URL exists, the editor is not called again
        $reused = $resizer->resize('https://example.com/uploads/photo.jpg', 300, 200);

        self::assertSame('https://example.com/uploads/photo-300x200-c-default.jpg.webp', $generated);
        self::assertSame($generated, $reused);
    }

    public function testKeepsTheExtensionWhenTheOutputFormatIsNotSupported(): void
    {
        touch($this->uploads . '/photo.jpg');
        Functions\when('wp_get_image_editor_output_format')->justReturn(['image/jpeg' => 'image/avif']);
        Functions\when('wp_image_editor_supports')->justReturn(false);
        $editor = $this->mockEditor();
        $editor->shouldReceive('save')->once()->with($this->uploads . '/photo-300x200-c-default.jpg')->andReturnUsing(fn (string $path): array => ['path' => $path]);

        self::assertSame('https://example.com/uploads/photo-300x200-c-default.jpg', (new ImageResizer())->resize('https://example.com/uploads/photo.jpg', 300, 200));
    }

    public function testReturnsTheSourceWhenTheEditorSavesAnotherFile(): void
    {
        touch($this->uploads . '/photo.jpg');
        $editor = $this->mockEditor();
        $editor->shouldReceive('save')->once()->andReturn(['path' => $this->uploads . '/photo-300x200-c-default.avif']);

        self::assertSame('https://example.com/uploads/photo.jpg', (new ImageResizer())->resize('https://example.com/uploads/photo.jpg', 300, 200));
    }

    #[RequiresFunction('imagewebp')]
    public function testConvertsTheImageToWebp(): void
    {
        $image = imagecreatetruecolor(10, 10);
        imagepng($image, $this->uploads . '/photo.png');
        Functions\when('wp_check_filetype')->justReturn(['ext' => 'png', 'type' => 'image/png']);

        $url = (new ImageResizer())->toWebp('https://example.com/uploads/photo.png');

        self::assertSame('https://example.com/uploads/photo.png.webp', $url);
        self::assertSame('image/webp', mime_content_type($this->uploads . '/photo.png.webp'));
    }

    public function testDoesNotReuseTheWebpImageOfAnotherAttachment(): void
    {
        touch($this->uploads . '/photo.jpg', time() - 60);
        touch($this->uploads . '/photo.webp');
        touch($this->uploads . '/photo.jpg.webp');
        Functions\expect('wp_check_filetype')->never();

        $resizer = new ImageResizer();

        self::assertSame('https://example.com/uploads/photo.jpg.webp', $resizer->toWebp('https://example.com/uploads/photo.jpg'));
        self::assertSame('https://example.com/uploads/photo.webp', $resizer->toWebp('https://example.com/uploads/photo.webp'));
    }

    public function testDeletesTheGeneratedFiles(): void
    {
        $files = ['photo.jpg', 'photo.jpg.webp', 'photo-300x200-c-default.jpg', 'photo-300x200-c-top-center.jpg.webp', 'photo-300x0-c-f.jpg', 'photo-300x200-c-default.jpg.avif', 'photo-large.jpg', 'photo-150x150.jpg', 'photo-150x150.jpg.webp', 'photo-2-300x200-c-default.jpg', 'other-300x200-c-default.jpg'];
        foreach ($files as $file) {
            touch($this->uploads . '/' . $file);
        }

        (new ImageResizer())->deleteGeneratedFiles($this->uploads . '/photo.jpg', [$this->uploads . '/photo-150x150.jpg']);

        self::assertSame(['other-300x200-c-default.jpg', 'photo-150x150.jpg', 'photo-2-300x200-c-default.jpg', 'photo-large.jpg', 'photo.jpg'], $this->listFiles());
    }

    public function testKeepsTheFilesOfAWebpAttachmentWithTheSameName(): void
    {
        // photo.webp is another attachment, uploaded in the same directory as photo.jpg, with its own sizes and resized versions
        $webpAttachment = ['photo.webp', 'photo-150x150.webp', 'photo-300x200-c-default.webp'];
        foreach (['photo.jpg', 'photo-150x150.jpg', 'photo.jpg.webp', 'photo-300x200-c-default.jpg', 'photo-300x200-c-default.jpg.webp', ...$webpAttachment] as $file) {
            touch($this->uploads . '/' . $file);
        }

        (new ImageResizer())->deleteGeneratedFiles($this->uploads . '/photo.jpg', [$this->uploads . '/photo-150x150.jpg']);

        self::assertSame(['photo-150x150.jpg', 'photo-150x150.webp', 'photo-300x200-c-default.webp', 'photo.jpg', 'photo.webp'], $this->listFiles());
    }

    public function testTheCleanerDeletesTheGeneratedFilesOfTheAttachment(): void
    {
        foreach (['photo.jpg', 'photo-300x200-c-default.jpg', 'photo-150x150.jpg.webp', 'photo-scaled.jpg.webp'] as $file) {
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

    private function mockEditor(): MockInterface
    {
        $editor = \Mockery::mock();
        $editor->shouldReceive('get_size')->andReturn(['width' => 600, 'height' => 400]);
        $editor->shouldReceive('crop');
        $editor->shouldReceive('set_quality');
        Functions\when('wp_get_image_editor')->justReturn($editor);
        Functions\when('is_wp_error')->justReturn(false);

        return $editor;
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

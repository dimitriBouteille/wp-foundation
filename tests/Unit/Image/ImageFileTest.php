<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\Image;

use Brain\Monkey\Functions;
use Dbout\WpFoundation\Image\ImageFile;
use Dbout\WpFoundation\Tests\TestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class ImageFileTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Functions\when('wp_upload_dir')->justReturn([
            'basedir' => self::fixturePath('wp-content') . '/uploads',
            'baseurl' => 'https://example.com/app/uploads',
            'error' => false,
        ]);
        Functions\when('content_url')->justReturn('https://example.com/app');
        Functions\when('home_url')->justReturn('https://example.com');
        Functions\when('network_home_url')->justReturn('https://example.com/');
        Functions\when('untrailingslashit')->alias(fn (string $value): string => rtrim($value, '/\\'));
    }

    /**
     * @return iterable<string, array{string, string, string, string, string}>
     */
    public static function sourceProvider(): iterable
    {
        yield 'absolute url of an upload' => ['https://example.com/app/uploads/2024/05/Photo.JPG?ver=2#top', '/uploads/2024/05', 'https://example.com/app/uploads/2024/05', 'Photo', 'jpg'];
        yield 'url with another scheme' => ['http://example.com/app/uploads/photo.png', '/uploads', 'https://example.com/app/uploads', 'photo', 'png'];
        yield 'protocol relative url' => ['//example.com/app/uploads/photo.png', '/uploads', 'https://example.com/app/uploads', 'photo', 'png'];
        yield 'relative url' => ['/app/uploads/2024/05/photo.jpg', '/uploads/2024/05', '/app/uploads/2024/05', 'photo', 'jpg'];
        yield 'path of an upload' => [self::fixturePath('wp-content') . '/uploads/2024/05/photo.jpg', '/uploads/2024/05', 'https://example.com/app/uploads/2024/05', 'photo', 'jpg'];
        yield 'url of a theme image' => ['https://example.com/app/themes/theme/images/logo.svg', '/themes/theme/images', 'https://example.com/app/themes/theme/images', 'logo', 'svg'];
    }

    #[DataProvider('sourceProvider')]
    public function testResolvesTheFileOfTheSource(string $src, string $directory, string $directoryUrl, string $filename, string $extension): void
    {
        $file = ImageFile::fromSource($src);

        self::assertNotNull($file);
        self::assertSame(self::fixturePath('wp-content') . $directory, $file->directoryPath);
        self::assertSame($directoryUrl, $file->directoryUrl);
        self::assertSame($filename, $file->filename);
        self::assertSame($extension, $file->extension);
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function externalSourceProvider(): iterable
    {
        yield 'other domain' => ['https://cdn.example.org/app/uploads/photo.jpg'];
        yield 'path outside wp-content' => ['/etc/passwd'];
        yield 'url outside wp-content' => ['https://example.com/wp-includes/images/logo.png'];
    }

    #[DataProvider('externalSourceProvider')]
    public function testReturnsNullOutsideWpContent(string $src): void
    {
        self::assertNull(ImageFile::fromSource($src));
    }

    public function testIgnoresTheUploadsDirectoryInError(): void
    {
        Functions\when('wp_upload_dir')->justReturn(['basedir' => '', 'baseurl' => '', 'error' => 'Unable to create directory.']);

        $file = ImageFile::fromSource('https://example.com/app/uploads/photo.jpg');

        self::assertNotNull($file);
        self::assertSame(self::fixturePath('wp-content') . '/uploads', $file->directoryPath);
    }

    public function testBuildsThePathsOfTheFile(): void
    {
        $file = new ImageFile('/var/www/uploads', 'https://example.com/uploads', 'photo', 'jpg');
        $webp = $file->withBasename('photo', 'webp');

        self::assertSame('photo.jpg', $file->basename());
        self::assertSame('/var/www/uploads/photo.jpg', $file->path());
        self::assertSame('https://example.com/uploads/photo.jpg', $file->url());
        self::assertSame('https://example.com/uploads/photo.webp', $webp->url());
        self::assertSame('photo', (new ImageFile('/', '', 'photo', ''))->basename());
    }

    public function testDetectsTheSvg(): void
    {
        self::assertTrue((new ImageFile('/missing', '', 'logo', 'svg'))->isSvg());
        self::assertTrue((new ImageFile(self::fixturePath('vite/images'), '', 'logo', 'svg'))->isSvg());
        self::assertFalse((new ImageFile('/missing', '', 'logo', 'png'))->isSvg());
    }
}

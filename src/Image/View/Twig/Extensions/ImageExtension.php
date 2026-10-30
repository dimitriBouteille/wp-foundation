<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Image\View\Twig\Extensions;

use Dbout\WpFoundation\Image\ImageResizer;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

/**
 * Twig filters to resize the images and convert them to WebP: `{{ image.src|resize(800, 600, 'center')|towebp }}`.
 */
final class ImageExtension extends AbstractExtension
{
    public function __construct(
        private readonly ImageResizer $resizer = new ImageResizer(),
    ) {}

    /**
     * @return TwigFilter[]
     */
    #[\Override]
    public function getFilters(): array
    {
        return [
            new TwigFilter('resize', [$this->resizer, 'resize']),
            new TwigFilter('towebp', [$this->resizer, 'toWebp']),
        ];
    }
}

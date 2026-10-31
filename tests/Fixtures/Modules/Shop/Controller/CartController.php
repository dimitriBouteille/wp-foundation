<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Fixtures\Modules\Shop\Controller;

use Dbout\WpFoundation\Routing\Attribute\Route;
use Dbout\WpFoundation\Tests\Fixtures\Http\RecordedResponse;

#[Route('/cart', name: 'cart', methods: ['GET', 'POST'])]
final class CartController extends AbstractController
{
    public function __invoke(): RecordedResponse
    {
        return new RecordedResponse('cart');
    }
}

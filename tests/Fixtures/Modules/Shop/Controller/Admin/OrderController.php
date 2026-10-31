<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Fixtures\Modules\Shop\Controller\Admin;

use Dbout\WpFoundation\Routing\Attribute\Route;
use Dbout\WpFoundation\Tests\Fixtures\Http\RecordedResponse;

final class OrderController
{
    #[Route('/admin/orders/{reference}', name: 'admin_order')]
    public function show(string $reference): RecordedResponse
    {
        return new RecordedResponse('admin_order', ['reference' => $reference]);
    }
}

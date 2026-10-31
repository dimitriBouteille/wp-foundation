<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Fixtures\Modules\Blog\Controller;

use Dbout\WpFoundation\Routing\Attribute\Route;
use Dbout\WpFoundation\Tests\Fixtures\Http\RecordedResponse;
use Symfony\Component\HttpFoundation\Request;

final class PostController
{
    #[Route('/', name: 'home')]
    public function home(): RecordedResponse
    {
        return new RecordedResponse('home');
    }

    #[Route('/blog', name: 'blog_index', methods: ['GET'])]
    public function index(): RecordedResponse
    {
        return new RecordedResponse('blog_index');
    }

    #[Route('/blog/{id}', name: 'blog_show', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function show(int $id, Request $request): RecordedResponse
    {
        return new RecordedResponse('blog_show', ['id' => $id, 'request' => $request]);
    }

    public function helper(): void {}
}

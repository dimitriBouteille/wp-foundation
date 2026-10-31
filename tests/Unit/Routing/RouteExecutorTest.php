<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Tests\Unit\Routing;

use Brain\Monkey\Actions;
use Brain\Monkey\Filters;
use Dbout\WpFoundation\Facades\Log;
use Dbout\WpFoundation\Http\Exception\AccessDeniedHttpException;
use Dbout\WpFoundation\Http\Exception\HttpException;
use Dbout\WpFoundation\Http\Exception\NotFoundHttpException;
use Dbout\WpFoundation\Http\Response\ResponseInterface;
use Dbout\WpFoundation\Routing\RouteExecutor;
use Dbout\WpFoundation\Tests\Fixtures\Http\RecordedResponse;
use Dbout\WpFoundation\Tests\TestCase;
use Illuminate\Container\Container;
use Symfony\Component\HttpFoundation\Request;

final class RouteExecutorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RecordedResponse::$sent = [];
    }

    public function testCastsTheRouteParametersToTheTypeOfTheAction(): void
    {
        $controller = fn (int $id, float $price, bool $enabled, string $slug, $raw): RecordedResponse => new RecordedResponse('typed', ['id' => $id, 'price' => $price, 'enabled' => $enabled, 'slug' => $slug, 'raw' => $raw]);

        $this->execute($controller, ['id' => '12', 'price' => '9.5', 'enabled' => 'true', 'slug' => 'first-aid', 'raw' => '7', '_route' => 'typed']);

        self::assertSame(['id' => 12, 'price' => 9.5, 'enabled' => true, 'slug' => 'first-aid', 'raw' => '7'], RecordedResponse::$sent[0]->data);
    }

    public function testInjectsTheRequestAndTheDependencies(): void
    {
        $request = Request::create('/blog');
        $controller = fn (Request $request, \ArrayObject $service): RecordedResponse => new RecordedResponse('injected', ['request' => $request, 'service' => $service]);

        $this->execute($controller, [], $request);

        self::assertSame($request, RecordedResponse::$sent[0]->data['request']);
        self::assertInstanceOf(\ArrayObject::class, RecordedResponse::$sent[0]->data['service']);
    }

    public function testBuildsTheControllerClassWithTheContainer(): void
    {
        $this->execute([InvokableController::class, '__invoke'], []);
        $this->execute(InvokableController::class, []);

        self::assertCount(2, RecordedResponse::$sent);
    }

    public function testTheNotFoundExceptionsDisplayTheNotFoundPage(): void
    {
        Filters\expectAdded('do_redirect_guess_404_permalink')->twice();
        Actions\expectAdded('wp')->twice();

        $this->execute(fn () => throw new NotFoundHttpException(), []);
        $this->execute(fn () => throw new HttpException(404), []);
    }

    public function testTheOtherHttpExceptionsDisplayTheErrorPage(): void
    {
        \Brain\Monkey\Functions\when('esc_html')->returnArg();
        \Brain\Monkey\Functions\expect('wp_die')
            ->once()
            ->with('Access denied.', '', ['response' => 403])
            ->andThrow(new \RuntimeException('wp_die'));

        $this->expectExceptionMessage('wp_die');

        $this->execute(fn () => throw new AccessDeniedHttpException('Access denied.'), []);
    }

    public function testTheOtherExceptionsAreLoggedAndThrownAgain(): void
    {
        $exception = new \DomainException('Unexpected error.');
        Log::shouldReceive('error')->once()->with('Unexpected error.', \Mockery::on(fn (array $context): bool => $context['exception'] === $exception));

        $this->expectExceptionObject($exception);

        $this->execute(fn () => throw $exception, []);
    }

    public function testTheActionMustReturnAResponse(): void
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage('The controller must return an instance of ' . ResponseInterface::class . ', string returned.');

        $this->execute(fn (): string => '<html>', []);
    }

    public function testTheControllerMustBeCallable(): void
    {
        Log::shouldReceive('error')->once();

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('The route controller is not callable.');

        $this->execute([InvokableController::class, 'unknown'], []);
    }

    /**
     * @param callable|array{0: class-string|object, 1: string}|string $controller
     * @param array<string, mixed> $parameters
     */
    private function execute(callable|array|string $controller, array $parameters, ?Request $request = null): void
    {
        (new RouteExecutor(new Container(), $controller, $parameters, $request ?? Request::create('/')))->execute();
    }
}

final class InvokableController
{
    public function __invoke(): RecordedResponse
    {
        return new RecordedResponse('invokable');
    }
}

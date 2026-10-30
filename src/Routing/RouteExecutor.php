<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\Routing;

use Dbout\WpFoundation\Facades\Log;
use Dbout\WpFoundation\Http\Exception\HttpException;
use Dbout\WpFoundation\Http\Exception\NotFoundHttpException;
use Dbout\WpFoundation\Http\Response\ErrorResponse;
use Dbout\WpFoundation\Http\Response\NotFoundResponse;
use Dbout\WpFoundation\Http\Response\ResponseInterface;
use Illuminate\Contracts\Container\Container;
use Symfony\Component\HttpFoundation\Request;

/**
 * Executes the controller of the matched route.
 *
 * The controller is resolved and called through the container (like Laravel does):
 *  - the dependencies of the constructor and of the action are injected,
 *  - the current {@see Request} is injected when the action asks for it,
 *  - the route parameters are passed by name, cast to the scalar type of the parameter.
 *
 * The action must return a {@see ResponseInterface}, which is sent. HTTP exceptions are
 * converted into responses, other exceptions are logged and thrown again.
 */
class RouteExecutor
{
    /**
     * @param callable|array{0: class-string|object, 1: string}|string $controller     [class, method], invokable class or function name
     * @param array<string, mixed> $parameters      The route parameters
     */
    public function __construct(
        protected Container $container,
        protected mixed $controller,
        protected array $parameters,
        protected Request $request,
    ) {}

    /**
     * @throws \Throwable
     */
    public function execute(): void
    {
        try {
            $response = $this->callController();
        } catch (HttpException $exception) {
            $response = $this->handleHttpException($exception);
        } catch (\Throwable $exception) {
            Log::error($exception->getMessage(), [
                'exception' => $exception,
                'controller' => $this->controller,
            ]);

            throw $exception;
        }

        if (!$response instanceof ResponseInterface) {
            throw new \UnexpectedValueException(sprintf(
                'The controller must return an instance of %s, %s returned.',
                ResponseInterface::class,
                get_debug_type($response),
            ));
        }

        $response->send();
    }

    /**
     * @throws \Throwable
     */
    protected function callController(): mixed
    {
        $callable = $this->resolveCallable();

        return $this->container->call($callable, [
            ...$this->castParameters($callable),
            Request::class => $this->request,
        ]);
    }

    /**
     * @throws \Throwable
     */
    protected function resolveCallable(): callable
    {
        $controller = $this->controller;
        if (is_array($controller) && is_string($controller[0])) {
            $controller = [$this->container->make($controller[0]), $controller[1]];
        } elseif (is_string($controller) && class_exists($controller)) {
            $controller = $this->container->make($controller);
        }

        if (!is_callable($controller)) {
            throw new \InvalidArgumentException('The route controller is not callable.');
        }

        return $controller;
    }

    /**
     * Keeps the route parameters expected by the action (internal parameters like "_route" are removed)
     * and casts them to the scalar type of the action parameter.
     *
     * @throws \ReflectionException
     * @return array<string, mixed>
     */
    protected function castParameters(callable $callable): array
    {
        $reflection = new \ReflectionFunction(\Closure::fromCallable($callable));

        $parameters = [];
        foreach ($reflection->getParameters() as $parameter) {
            $name = $parameter->getName();
            if (!array_key_exists($name, $this->parameters)) {
                continue;
            }

            $type = $parameter->getType();
            $parameters[$name] = $type instanceof \ReflectionNamedType
                ? $this->castValue($type->getName(), $this->parameters[$name])
                : $this->parameters[$name];
        }

        return $parameters;
    }

    protected function castValue(string $type, mixed $value): mixed
    {
        if (!is_scalar($value)) {
            return $value;
        }

        return match ($type) {
            'int' => (int) $value,
            'string' => (string) $value,
            'float' => (float) $value,
            'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            default => $value,
        };
    }

    protected function handleHttpException(HttpException $exception): ResponseInterface
    {
        if ($exception instanceof NotFoundHttpException || $exception->getStatusCode() === 404) {
            return new NotFoundResponse();
        }

        return new ErrorResponse($exception->getStatusCode(), $exception->getMessage());
    }
}

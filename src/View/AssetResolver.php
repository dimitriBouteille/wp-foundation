<?php
/**
 * Copyright (c) Dimitri BOUTEILLE (https://github.com/dimitriBouteille)
 * See LICENSE.txt for license details.
 */
declare(strict_types=1);

namespace Dbout\WpFoundation\View;

use Dbout\WpFoundation\Facades\Log;
use Dbout\WpFoundation\View\Resolver\ResolverInterface;

class AssetResolver
{
    /**
     * @var array<string, string|null>
     */
    protected array $contentCache = [];

    /**
     * @var array<string, true>
     */
    private array $loggedErrors = [];

    /**
     * @param array<string, ResolverInterface> $areas
     */
    public function __construct(
        private readonly array $areas,
    ) {}

    public function resolveUrl(string $fileKey): ?string
    {
        try {
            [$area, $path] = $this->getFileData($fileKey);
            $resolvedPath = $this->getResolver($area)->resolve($path);
            if ($resolvedPath === null || $resolvedPath === '') {
                return null;
            }

            return sprintf(
                '%s/%s',
                rtrim(home_url(), '/'),
                trim($resolvedPath, '/'),
            );
        } catch (\Exception $exception) {
            $this->logError($fileKey, $exception);

            return null;
        }
    }

    public function resolvePath(string $fileKey): ?string
    {
        try {
            [$area, $path] = $this->getFileData($fileKey);

            return $this->getResolver($area)->getPath($path);
        } catch (\Exception $exception) {
            $this->logError($fileKey, $exception);

            return null;
        }
    }

    public function getContent(string $fileKey): ?string
    {
        if (array_key_exists($fileKey, $this->contentCache)) {
            return $this->contentCache[$fileKey];
        }

        try {
            $path = $this->resolvePath($fileKey);
            $content = null;
            if ($path !== null && file_exists($path)) {
                $content = file_get_contents($path) ?: null;
            }

            $this->contentCache[$fileKey] = $content;

            return $content;
        } catch (\Exception $exception) {
            $this->logError($fileKey, $exception);
            $this->contentCache[$fileKey] = null;
        }

        return null;
    }

    /**
     * Logs once per asset, without ever throwing, so that a missing asset does not break the page.
     */
    private function logError(string $fileKey, \Exception $exception): void
    {
        if (isset($this->loggedErrors[$fileKey])) {
            return;
        }

        $this->loggedErrors[$fileKey] = true;

        try {
            Log::warning(sprintf('Unable to resolve the asset %s: %s', $fileKey, $exception->getMessage()), [
                'exception' => $exception,
            ]);
        } catch (\Throwable) {
        }
    }

    /**
     * @throws \Exception
     */
    private function getResolver(string $area): ResolverInterface
    {
        $resolver = $this->areas[$area] ?? null;
        if (!$resolver instanceof ResolverInterface) {
            throw new \Exception('Invalid area.');
        }

        return $resolver;
    }

    /**
     * @throws \Exception
     * @return array{string, string}
     */
    private function getFileData(string $fileKey): array
    {
        $parts = explode('::', $fileKey);
        if (count($parts) !== 2) {
            throw new \Exception(sprintf('Invalid file path %s', $fileKey));
        }

        return [
            $parts[0],
            $parts[1],
        ];
    }
}

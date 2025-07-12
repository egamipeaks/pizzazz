<?php

namespace EgamiPeaks\Pizzazz\Services;

use Illuminate\Support\Facades\Cache;

class PageCacheRegistry
{
    private const REGISTRY_KEY = 'pizzazz:cache_registry';

    private const PAGE_PREFIX = 'pizzazz:page:';

    /**
     * Register a cache key for tracking
     */
    public function register(string $cacheKey, string $pageIdentifier): void
    {
        $registry = $this->getRegistry();

        if (! isset($registry[$pageIdentifier])) {
            $registry[$pageIdentifier] = [];
        }

        $registry[$pageIdentifier][] = $cacheKey;

        Cache::forever(self::REGISTRY_KEY, $registry);
    }

    /**
     * Get all cache keys for a specific page
     */
    public function getPageKeys(string $pageIdentifier): array
    {
        $registry = $this->getRegistry();

        return $registry[$pageIdentifier] ?? [];
    }

    /**
     * Get all cache keys
     */
    public function getAllKeys(): array
    {
        $registry = $this->getRegistry();
        $allKeys = [];

        foreach ($registry as $keys) {
            $allKeys = array_merge($allKeys, $keys);
        }

        return array_unique($allKeys);
    }

    /**
     * Remove cache key from registry
     */
    public function unregister(string $cacheKey): void
    {
        $registry = $this->getRegistry();

        foreach ($registry as $pageIdentifier => $keys) {
            $registry[$pageIdentifier] = array_filter($keys, fn ($key) => $key !== $cacheKey);

            if (empty($registry[$pageIdentifier])) {
                unset($registry[$pageIdentifier]);
            }
        }

        Cache::forever(self::REGISTRY_KEY, $registry);
    }

    /**
     * Remove all keys for a specific page from registry
     */
    public function unregisterPage(string $pageIdentifier): void
    {
        $registry = $this->getRegistry();
        unset($registry[$pageIdentifier]);

        Cache::forever(self::REGISTRY_KEY, $registry);
    }

    /**
     * Clear the entire registry
     */
    public function clear(): void
    {
        Cache::forget(self::REGISTRY_KEY);
    }

    /**
     * Get the full cache key with prefix
     */
    public function getFullCacheKey(string $key): string
    {
        return self::PAGE_PREFIX.$key;
    }

    /**
     * Generate page identifier from path
     */
    public function getPageIdentifier(string $path): string
    {
        return md5($path);
    }

    /**
     * Get the current registry
     */
    private function getRegistry(): array
    {
        return Cache::get(self::REGISTRY_KEY, []);
    }
}

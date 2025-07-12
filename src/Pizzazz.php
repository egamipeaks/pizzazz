<?php

namespace EgamiPeaks\Pizzazz;

use EgamiPeaks\Pizzazz\Exceptions\PageCacheException;
use EgamiPeaks\Pizzazz\Services\PageCacheKeyService;
use EgamiPeaks\Pizzazz\Services\PageCacheLogger;
use EgamiPeaks\Pizzazz\Services\PageCacheRegistry;
use Illuminate\Http\Request;

class Pizzazz
{
    private array $disallowedQueryVars;

    public function __construct(
        protected PageCacheKeyService $keyService,
        protected PageCacheLogger $logger,
        protected PageCacheRegistry $registry
    ) {
        $this->disallowedQueryVars = config('pizzazz.disallowed_query_vars', []);
    }

    public function canCache(Request $request): bool
    {
        try {
            if (! config('pizzazz.enabled')) {
                throw new PageCacheException('Page cache is disabled');
            }

            if ($request->method() !== 'GET') {
                throw new PageCacheException('Not GET request');
            }

            if (count(array_intersect($this->disallowedQueryVars, array_keys($request->query()))) !== 0) {
                throw new PageCacheException('Disallowed query vars');
            }

            $cacheAuthenticated = config('pizzazz.cache_authenticated_requests', false);

            if (auth()->check() && ! $cacheAuthenticated) {
                throw new PageCacheException('Authenticated request');
            }

            return true;
        } catch (PageCacheException $e) {
            $this->logger->log("Can't Cache page", [
                'url' => $request->fullUrl(),
                'message' => $e->getMessage(),
            ]);

            return false;
        }
    }

    public function getCache(Request $request): ?string
    {
        $url = $request->fullUrl();

        try {
            $key = $this->keyService->getKey($request);

            if ($cacheValue = cache()->get($key)) {
                $this->logger->log(sprintf('Serving cache: %s', $url), [
                    'key' => $key,
                ]);

                return $cacheValue;
            }
        } catch (PageCacheException $e) {
            $this->logger->log(sprintf("Can't Serve Cache %s: %s", $url, $e->getMessage()));
        }

        return null;
    }
}

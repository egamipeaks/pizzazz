<?php

namespace EgamiPeaks\Pizzazz;

use EgamiPeaks\Pizzazz\Exceptions\PageCacheException;
use EgamiPeaks\Pizzazz\Services\PageCacheKeyService;
use EgamiPeaks\Pizzazz\Services\PageCacheLogger;
use Illuminate\Http\Request;

class Pizzazz
{
    private array $disallowedQueryVars;

    public function __construct(
        protected PageCacheKeyService $keyService,
        protected PageCacheLogger $logger
    ) {
        $this->disallowedQueryVars = config('pizzazz.disallowed_query_vars', []);
    }

    public function canCache(Request $request): bool
    {
        try {
            if (! config('pizzazz.enabled')) {
                throw new PageCacheException('Page cache is disabled');
            }

            if ($request->server('SCRIPT_NAME') !== '/index.php') {
                throw new PageCacheException('Not index.php');
            }

            if (empty($request->getHost())) {
                throw new PageCacheException('No host');
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
            $tags = $this->keyService->getTags($request);
            $key = $this->keyService->getKey($request);

            if ($cacheValue = cache()->tags($tags)->get($key)) {
                $this->logger->log(sprintf('Serving cache: %s', $url), [
                    'tags' => $tags,
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

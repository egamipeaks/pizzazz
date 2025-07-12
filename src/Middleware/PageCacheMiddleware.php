<?php

namespace EgamiPeaks\Pizzazz\Middleware;

use Closure;
use EgamiPeaks\Pizzazz\Pizzazz;
use EgamiPeaks\Pizzazz\Services\PageCacheKeyService;
use EgamiPeaks\Pizzazz\Services\PageCacheLogger;
use EgamiPeaks\Pizzazz\Services\PageCacheRegistry;
use Illuminate\Http\Request;

class PageCacheMiddleware
{
    public function __construct(
        protected Pizzazz $pizzazz,
        protected PageCacheLogger $logger,
        protected PageCacheKeyService $keyService,
        protected PageCacheRegistry $registry
    ) {
        //
    }

    public function handle(Request $request, Closure $next)
    {
        $url = $request->fullUrl();

        if (! $this->pizzazz->canCache($request)) {
            return $next($request);
        }

        if ($cache = $this->pizzazz->getCache($request)) {
            $cache = str_replace('<body', '<body data-cached="true"', $cache);

            return response($cache)->header('X-Cache', 'HIT');
        }

        /** @var \Illuminate\Http\Response $response */
        $response = $next($request);

        if ($response->status() !== 200) {
            return $response;
        }

        $minContentLength = config('pizzazz.min_content_length', 0);
        if (strlen($response->getContent()) <= $minContentLength) {
            return $response;
        }

        $key = $this->keyService->getKey($request);
        $pageIdentifier = $this->keyService->getPageIdentifier($request);

        $this->logger->log(sprintf('Caching page: %s', $url), [
            'key' => $key,
            'page_identifier' => $pageIdentifier,
        ]);

        $cacheLengthInSeconds = config('pizzazz.cache_length_in_seconds', 86400);
        cache()->put($key, $response->getContent(), now()->addSeconds($cacheLengthInSeconds));

        // Register the cache key for tracking
        $this->registry->register($key, $pageIdentifier);

        return $response;
    }
}

<?php

namespace EgamiPeaks\Pizzazz\Middleware;

use Closure;
use EgamiPeaks\Pizzazz\Pizzazz;
use EgamiPeaks\Pizzazz\Services\PageCacheKeyService;
use EgamiPeaks\Pizzazz\Services\PageCacheLogger;
use Illuminate\Http\Request;

class PageCacheMiddleware
{
    public function __construct(
        protected Pizzazz $pizzazz,
        protected PageCacheLogger $logger,
        protected PageCacheKeyService $keyService,
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

        $tags = $this->keyService->getTags($request);
        $key = $this->keyService->getKey($request);

        $this->logger->log(sprintf('Caching page: %s', $url), [
            'tags' => $tags,
            'key' => $key,
        ]);

        $cacheLengthInSeconds = config('pizzazz.cache_length_in_seconds', 86400);
        cache()->tags($tags)->put($key, $response->getContent(), now()->addSeconds($cacheLengthInSeconds));

        return $response;
    }
}

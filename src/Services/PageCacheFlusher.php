<?php

namespace EgamiPeaks\Pizzazz\Services;

class PageCacheFlusher
{
    public function __construct(
        protected PageCacheKeyService $keyService,
        protected PageCacheLogger $logger
    ) {}

    public function flush(): void
    {
        cache()->tags('page')->flush();
    }

    public function flushUrl(string $url): void
    {
        // Create a mock request for the URL to maintain compatibility
        $request = \Illuminate\Http\Request::create($url);
        $tag = $this->keyService->getPageTag($request);

        $this->logger->log('Flushing cache for URL', [
            'url' => $url,
            'tag' => $tag,
        ]);

        cache()->tags($tag)->flush();
    }
}

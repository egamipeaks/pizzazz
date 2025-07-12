<?php

namespace EgamiPeaks\Pizzazz\Services;

use Illuminate\Support\Facades\Cache;

class PageCacheFlusher
{
    public function __construct(
        protected PageCacheKeyService $keyService,
        protected PageCacheLogger $logger,
        protected PageCacheRegistry $registry
    ) {}

    public function flush(): void
    {
        $allKeys = $this->registry->getAllKeys();

        $this->logger->log('Flushing all page cache', [
            'keys_count' => count($allKeys),
        ]);

        foreach ($allKeys as $key) {
            Cache::forget($key);
        }

        $this->registry->clear();
    }

    public function flushUrl(string $url): void
    {
        // Create a mock request for the URL to maintain compatibility
        $request = \Illuminate\Http\Request::create($url);
        $pageIdentifier = $this->keyService->getPageIdentifier($request);

        $this->logger->log('Flushing cache for URL', [
            'url' => $url,
            'page_identifier' => $pageIdentifier,
        ]);

        $keys = $this->registry->getPageKeys($pageIdentifier);

        foreach ($keys as $key) {
            Cache::forget($key);
        }

        $this->registry->unregisterPage($pageIdentifier);
    }
}

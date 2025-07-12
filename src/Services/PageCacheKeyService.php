<?php

namespace EgamiPeaks\Pizzazz\Services;

use Illuminate\Http\Request;

class PageCacheKeyService
{
    private array $requiredQueryArgs;

    public function __construct(
        protected PageCacheRegistry $registry
    ) {
        $this->requiredQueryArgs = config('pizzazz.required_query_args', []);
    }

    public function getKey(Request $request): string
    {
        $path = $request->getPathInfo();

        $urlPathKey = match ($path) {
            '', '/', '/index.php' => 'home',
            default => trim($path, '/'),
        };

        $urlKey = sprintf('path=%s', $urlPathKey);

        $query = $this->getCleanedQueryString($request);
        $queryKey = $query ? md5($query) : '0';

        $key = sprintf(
            '%s:query=%s',
            $urlKey,
            $queryKey,
        );

        return $this->registry->getFullCacheKey($key);
    }

    public function getPageIdentifier(Request $request): string
    {
        return $this->registry->getPageIdentifier($request->getPathInfo());
    }

    private function getCleanedQueryString(Request $request): string
    {
        $queryParams = $request->query();

        $filteredParams = collect($queryParams)
            ->filter(function ($value, $key) {
                return in_array($key, $this->requiredQueryArgs);
            })
            ->sortKeys();

        return $filteredParams->isEmpty() ? '' : http_build_query($filteredParams->all());
    }
}

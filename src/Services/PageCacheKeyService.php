<?php

namespace EgamiPeaks\Pizzazz\Services;

use Illuminate\Http\Request;

class PageCacheKeyService
{
    private array $requiredQueryArgs;

    public function __construct()
    {
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

        return sprintf(
            '%s:query=%s',
            $urlKey,
            $queryKey,
        );
    }

    public function getTags(Request $request): array
    {
        return ['page', $this->getPageTag($request)];
    }

    public function getPageTag(Request $request): string
    {
        return 'page:'.md5($request->getPathInfo());
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

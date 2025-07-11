<?php

namespace EgamiPeaks\Pizzazz\Services;

class PageCacheLogger
{
    protected bool $enabled;

    public function __construct()
    {
        $this->enabled = config('pizzazz.debug', true);
    }

    public function log(string $message, array $context = []): void
    {
        if (! $this->enabled) {
            return;
        }

        logger()->debug('pizzazz: '.$message, $context);
    }
}

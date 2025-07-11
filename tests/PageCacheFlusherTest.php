<?php

use EgamiPeaks\Pizzazz\Services\PageCacheFlusher;
use EgamiPeaks\Pizzazz\Services\PageCacheKeyService;
use EgamiPeaks\Pizzazz\Services\PageCacheLogger;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->keyService = new PageCacheKeyService;
    $this->logger = new PageCacheLogger;
    $this->flusher = new PageCacheFlusher($this->keyService, $this->logger);
});

describe('cache flushing', function () {
    it('flushes all page cache', function () {
        Cache::shouldReceive('tags')->with('page')->andReturnSelf();
        Cache::shouldReceive('flush')->once();

        $this->flusher->flush();

        expect(true)->toBeTrue(); // Test passes if no exception
    });

    it('flushes specific URL cache', function () {
        $url = 'https://example.com/test';
        $expectedTag = 'page:'.md5('/test');

        Cache::shouldReceive('tags')->with($expectedTag)->andReturnSelf();
        Cache::shouldReceive('flush')->once();

        $this->flusher->flushUrl($url);

        expect(true)->toBeTrue();
    });

    it('flushes home page cache correctly', function () {
        $url = 'https://example.com/';
        $expectedTag = 'page:'.md5('/');

        Cache::shouldReceive('tags')->with($expectedTag)->andReturnSelf();
        Cache::shouldReceive('flush')->once();

        $this->flusher->flushUrl($url);

        expect(true)->toBeTrue();
    });

    it('flushes nested page cache correctly', function () {
        $url = 'https://example.com/blog/post-title';
        $expectedTag = 'page:'.md5('/blog/post-title');

        Cache::shouldReceive('tags')->with($expectedTag)->andReturnSelf();
        Cache::shouldReceive('flush')->once();

        $this->flusher->flushUrl($url);

        expect(true)->toBeTrue();
    });

    it('handles URLs with query parameters', function () {
        $url = 'https://example.com/search?q=test&page=2';
        $expectedTag = 'page:'.md5('/search');

        Cache::shouldReceive('tags')->with($expectedTag)->andReturnSelf();
        Cache::shouldReceive('flush')->once();

        $this->flusher->flushUrl($url);

        expect(true)->toBeTrue();
    });
});

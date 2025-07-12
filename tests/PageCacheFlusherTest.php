<?php

use EgamiPeaks\Pizzazz\Services\PageCacheFlusher;
use EgamiPeaks\Pizzazz\Services\PageCacheKeyService;
use EgamiPeaks\Pizzazz\Services\PageCacheLogger;
use EgamiPeaks\Pizzazz\Services\PageCacheRegistry;
use Illuminate\Support\Facades\Cache;

beforeEach(function () {
    $this->registry = new PageCacheRegistry;
    $this->keyService = new PageCacheKeyService($this->registry);
    $this->logger = new PageCacheLogger;
    $this->flusher = new PageCacheFlusher($this->keyService, $this->logger, $this->registry);
});

describe('cache flushing', function () {
    it('flushes all page cache', function () {
        $registryData = [
            'test1_id' => ['pizzazz:page:path=test1:query=0'],
            'test2_id' => ['pizzazz:page:path=test2:query=0'],
        ];

        // Mock registry get
        Cache::shouldReceive('get')->with('pizzazz:cache_registry', [])->andReturn($registryData);

        // Mock forgetting individual cache keys
        Cache::shouldReceive('forget')->with('pizzazz:page:path=test1:query=0')->once();
        Cache::shouldReceive('forget')->with('pizzazz:page:path=test2:query=0')->once();

        // Mock clearing the registry
        Cache::shouldReceive('forget')->with('pizzazz:cache_registry')->once();

        $this->flusher->flush();

        expect(true)->toBeTrue(); // Test passes if no exception
    });

    it('flushes specific URL cache', function () {
        $url = 'https://example.com/test';
        $pageIdentifier = md5('/test');

        $registryData = [
            $pageIdentifier => ['pizzazz:page:path=test:query=0', 'pizzazz:page:path=test:query=abc123'],
        ];

        // Mock registry get
        Cache::shouldReceive('get')->with('pizzazz:cache_registry', [])->andReturn($registryData);

        Cache::shouldReceive('forget')->with('pizzazz:page:path=test:query=0')->once();
        Cache::shouldReceive('forget')->with('pizzazz:page:path=test:query=abc123')->once();
        Cache::shouldReceive('forever')->with('pizzazz:cache_registry', \Mockery::type('array'))->once();

        $this->flusher->flushUrl($url);

        expect(true)->toBeTrue();
    });

    it('flushes home page cache correctly', function () {
        $url = 'https://example.com/';
        $pageIdentifier = md5('/');

        $registryData = [
            $pageIdentifier => ['pizzazz:page:path=home:query=0'],
        ];

        Cache::shouldReceive('get')->with('pizzazz:cache_registry', [])->andReturn($registryData);
        Cache::shouldReceive('forget')->with('pizzazz:page:path=home:query=0')->once();
        Cache::shouldReceive('forever')->with('pizzazz:cache_registry', \Mockery::type('array'))->once();

        $this->flusher->flushUrl($url);

        expect(true)->toBeTrue();
    });

    it('flushes nested page cache correctly', function () {
        $url = 'https://example.com/blog/post-title';
        $pageIdentifier = md5('/blog/post-title');

        $registryData = [
            $pageIdentifier => ['pizzazz:page:path=blog/post-title:query=0'],
        ];

        Cache::shouldReceive('get')->with('pizzazz:cache_registry', [])->andReturn($registryData);
        Cache::shouldReceive('forget')->with('pizzazz:page:path=blog/post-title:query=0')->once();
        Cache::shouldReceive('forever')->with('pizzazz:cache_registry', \Mockery::type('array'))->once();

        $this->flusher->flushUrl($url);

        expect(true)->toBeTrue();
    });

    it('handles URLs with query parameters', function () {
        $url = 'https://example.com/search?q=test&page=2';
        $pageIdentifier = md5('/search');

        $registryData = [
            $pageIdentifier => ['pizzazz:page:path=search:query=0'],
        ];

        Cache::shouldReceive('get')->with('pizzazz:cache_registry', [])->andReturn($registryData);
        Cache::shouldReceive('forget')->with('pizzazz:page:path=search:query=0')->once();
        Cache::shouldReceive('forever')->with('pizzazz:cache_registry', \Mockery::type('array'))->once();

        $this->flusher->flushUrl($url);

        expect(true)->toBeTrue();
    });
});

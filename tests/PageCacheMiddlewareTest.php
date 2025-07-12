<?php

use EgamiPeaks\Pizzazz\Middleware\PageCacheMiddleware;
use EgamiPeaks\Pizzazz\Pizzazz;
use EgamiPeaks\Pizzazz\Services\PageCacheKeyService;
use EgamiPeaks\Pizzazz\Services\PageCacheLogger;
use EgamiPeaks\Pizzazz\Services\PageCacheRegistry;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    $this->registry = new PageCacheRegistry;
    $this->keyService = new PageCacheKeyService($this->registry);
    $this->logger = new PageCacheLogger;
    $this->pizzazz = new Pizzazz($this->keyService, $this->logger, $this->registry);
    $this->middleware = new PageCacheMiddleware($this->pizzazz, $this->logger, $this->keyService, $this->registry);
});

describe('middleware cache hit', function () {
    it('returns cached response when cache exists', function () {
        Config::set('pizzazz.enabled', true);

        $request = Request::create('/test');
        $request->server->set('SCRIPT_NAME', '/index.php');

        $cachedContent = '<html><body>Cached content</body></html>';

        $this->mock('cache', function ($mock) use ($cachedContent) {
            $mock->shouldReceive('get')->with('pizzazz:page:path=test:query=0')->andReturn($cachedContent);
        });

        $response = $this->middleware->handle($request, function () use ($cachedContent) {
            return new Response($cachedContent);
        });

        expect($response->getContent())->toBe('<html><body data-cached="true">Cached content</body></html>');
        expect($response->headers->get('X-Cache'))->toBe('HIT');
    });

    it('passes through when no cache exists', function () {
        Config::set('pizzazz.enabled', true);

        $request = Request::create('/test');
        $request->server->set('SCRIPT_NAME', '/index.php');

        Cache::shouldReceive('get')->with('pizzazz:page:path=test:query=0')->andReturn(null);

        $freshContent = '<html><body>Fresh content</body></html>';

        Cache::shouldReceive('put')->with('pizzazz:page:path=test:query=0', $freshContent, \Mockery::any());
        Cache::shouldReceive('get')->with('pizzazz:cache_registry', [])->andReturn([]);
        Cache::shouldReceive('forever')->with('pizzazz:cache_registry', \Mockery::type('array'));

        $response = $this->middleware->handle($request, function () use ($freshContent) {
            return new Response($freshContent);
        });

        expect($response->getContent())->toBe($freshContent);
        expect($response->headers->get('X-Cache'))->toBeNull();
    });
});

describe('middleware cache storage', function () {
    it('stores response in cache when conditions are met', function () {
        Config::set('pizzazz.enabled', true);
        Config::set('pizzazz.cache_length_in_seconds', 3600);

        $request = Request::create('/test');
        $request->server->set('SCRIPT_NAME', '/index.php');

        $content = '<html><body>Content to cache</body></html>';

        // Mock cache miss
        Cache::shouldReceive('get')->with('pizzazz:page:path=test:query=0')->andReturn(null);

        // Mock cache storage
        Cache::shouldReceive('put')->with('pizzazz:page:path=test:query=0', $content, \Mockery::any());
        Cache::shouldReceive('get')->with('pizzazz:cache_registry', [])->andReturn([]);
        Cache::shouldReceive('forever')->with('pizzazz:cache_registry', \Mockery::type('array'));

        $response = $this->middleware->handle($request, function () use ($content) {
            return new Response($content, 200);
        });

        expect($response->getContent())->toBe($content);
        expect($response->getStatusCode())->toBe(200);
    });

    it('does not cache non-200 responses', function () {
        Config::set('pizzazz.enabled', true);

        $request = Request::create('/test');
        $request->server->set('SCRIPT_NAME', '/index.php');

        Cache::shouldReceive('get')->with('pizzazz:page:path=test:query=0')->andReturn(null);

        // Should not attempt to cache
        Cache::shouldNotReceive('put');

        $response = $this->middleware->handle($request, function () {
            return new Response('Not Found', 404);
        });

        expect($response->getStatusCode())->toBe(404);
    });

    it('does not cache responses below minimum content length', function () {
        Config::set('pizzazz.enabled', true);
        Config::set('pizzazz.min_content_length', 100);

        $request = Request::create('/test');
        $request->server->set('SCRIPT_NAME', '/index.php');

        $shortContent = 'Short';

        Cache::shouldReceive('get')->with('pizzazz:page:path=test:query=0')->andReturn(null);

        // Should not attempt to cache
        Cache::shouldNotReceive('put');

        $response = $this->middleware->handle($request, function () use ($shortContent) {
            return new Response($shortContent, 200);
        });

        expect($response->getContent())->toBe($shortContent);
    });

    it('caches responses above minimum content length', function () {
        Config::set('pizzazz.enabled', true);
        Config::set('pizzazz.min_content_length', 10);

        $request = Request::create('/test');
        $request->server->set('SCRIPT_NAME', '/index.php');

        $longContent = 'This is a long enough content to be cached';

        Cache::shouldReceive('get')->with('pizzazz:page:path=test:query=0')->andReturn(null);

        Cache::shouldReceive('put')->with('pizzazz:page:path=test:query=0', $longContent, \Mockery::any());
        Cache::shouldReceive('get')->with('pizzazz:cache_registry', [])->andReturn([]);
        Cache::shouldReceive('forever')->with('pizzazz:cache_registry', \Mockery::type('array'));

        $response = $this->middleware->handle($request, function () use ($longContent) {
            return new Response($longContent, 200);
        });

        expect($response->getContent())->toBe($longContent);
    });
});

describe('middleware bypass conditions', function () {
    it('bypasses cache when cannot cache', function () {
        Config::set('pizzazz.enabled', false);

        $request = Request::create('/test');

        $response = $this->middleware->handle($request, function () {
            return new Response('Uncached content');
        });

        expect($response->getContent())->toBe('Uncached content');
    });

    it('bypasses cache for POST requests', function () {
        Config::set('pizzazz.enabled', true);

        $request = Request::create('/test', 'POST');

        $response = $this->middleware->handle($request, function () {
            return new Response('POST response');
        });

        expect($response->getContent())->toBe('POST response');
    });
});

<?php

use EgamiPeaks\Pizzazz\Pizzazz;
use EgamiPeaks\Pizzazz\Services\PageCacheKeyService;
use EgamiPeaks\Pizzazz\Services\PageCacheLogger;
use EgamiPeaks\Pizzazz\Services\PageCacheRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

function createPizzazzInstance()
{
    return new Pizzazz(new PageCacheKeyService(new PageCacheRegistry), new PageCacheLogger, new PageCacheRegistry);
}

describe('canCache validation', function () {
    it('returns false when pizzazz is disabled', function () {
        Config::set('pizzazz.enabled', false);

        $pizzazz = createPizzazzInstance();

        $request = Request::create('/test');

        expect($pizzazz->canCache($request))->toBeFalse();
    });

    it('returns false for non-GET requests', function () {
        Config::set('pizzazz.enabled', true);

        $pizzazz = createPizzazzInstance();
        $request = Request::create('/test', 'POST');
        $request->server->set('SCRIPT_NAME', '/index.php');

        expect($pizzazz->canCache($request))->toBeFalse();
    });

    it('returns false when disallowed query vars are present', function () {
        Config::set('pizzazz.enabled', true);
        Config::set('pizzazz.disallowed_query_vars', ['nocache', 'debug']);

        $pizzazz = createPizzazzInstance();

        $request = Request::create('/test?nocache=1');
        $request->server->set('SCRIPT_NAME', '/index.php');

        expect($pizzazz->canCache($request))->toBeFalse();
    });

    it('returns false for authenticated requests when cache_authenticated_requests is false', function () {
        Config::set('pizzazz.enabled', true);
        Config::set('pizzazz.cache_authenticated_requests', false);

        $pizzazz = createPizzazzInstance();

        $request = Request::create('/test');
        $request->server->set('SCRIPT_NAME', '/index.php');

        // Mock authenticated user
        $this->mock('auth', function ($mock) {
            $mock->shouldReceive('check')->once()->andReturn(true);
        });

        expect($pizzazz->canCache($request))->toBeFalse();
    });

    it('returns true for authenticated requests when cache_authenticated_requests is true', function () {
        Config::set('pizzazz.enabled', true);
        Config::set('pizzazz.cache_authenticated_requests', true);

        $pizzazz = createPizzazzInstance();

        $request = Request::create('/test');
        $request->server->set('SCRIPT_NAME', '/index.php');

        $this->mock('auth', function ($mock) {
            $mock->shouldReceive('check')->once()->andReturn(true);
        });

        expect($pizzazz->canCache($request))->toBeTrue();
    });

    it('returns true for valid cacheable requests', function () {
        Config::set('pizzazz.enabled', true);
        Config::set('pizzazz.cache_authenticated_requests', false);

        $pizzazz = createPizzazzInstance();

        $request = Request::create('/test');
        $request->server->set('SCRIPT_NAME', '/index.php');

        $this->mock('auth', function ($mock) {
            $mock->shouldReceive('check')->once()->andReturn(false);
        });

        expect($pizzazz->canCache($request))->toBeTrue();
    });

    it('allows requests with query vars not in disallowed list', function () {
        Config::set('pizzazz.enabled', true);
        Config::set('pizzazz.disallowed_query_vars', ['nocache']);

        $pizzazz = createPizzazzInstance();

        $request = Request::create('/test?page=1&sort=name');
        $request->server->set('SCRIPT_NAME', '/index.php');

        $this->mock('auth', function ($mock) {
            $mock->shouldReceive('check')->once()->andReturn(false);
        });

        expect($pizzazz->canCache($request))->toBeTrue();
    });
});

describe('cache retrieval', function () {
    it('returns null when no cache exists', function () {
        $pizzazz = createPizzazzInstance();

        $request = Request::create('/test');

        $this->mock('cache', function ($mock) {
            $mock->shouldReceive('get')->with('pizzazz:page:path=test:query=0')->andReturn(null);
        });

        expect($pizzazz->getCache($request))->toBeNull();
    });

    it('returns cached content when cache exists', function () {
        $pizzazz = createPizzazzInstance();

        $request = Request::create('/test');
        $cachedContent = '<html><body>Cached content</body></html>';

        $this->mock('cache', function ($mock) use ($cachedContent) {
            $mock->shouldReceive('get')->with('pizzazz:page:path=test:query=0')->andReturn($cachedContent);
        });

        expect($pizzazz->getCache($request))->toBe($cachedContent);
    });

    it('handles cache with query parameters', function () {
        Config::set('pizzazz.required_query_args', ['page']);

        $pizzazz = createPizzazzInstance();

        $request = Request::create('/test?page=2&ignore=this');
        $cachedContent = '<html><body>Page 2 content</body></html>';

        $expectedQueryKey = md5('page=2');

        $this->mock('cache', function ($mock) use ($cachedContent, $expectedQueryKey) {
            $mock->shouldReceive('get')->with("pizzazz:page:path=test:query=$expectedQueryKey")->andReturn($cachedContent);
        });

        expect($pizzazz->getCache($request))->toBe($cachedContent);
    });

    it('handles home page caching', function () {
        $pizzazz = createPizzazzInstance();

        $request = Request::create('/');
        $cachedContent = '<html><body>Home page</body></html>';

        $this->mock('cache', function ($mock) use ($cachedContent) {
            $mock->shouldReceive('get')->with('pizzazz:page:path=home:query=0')->andReturn($cachedContent);
        });

        expect($pizzazz->getCache($request))->toBe($cachedContent);
    });
});

<?php

use EgamiPeaks\Pizzazz\Services\PageCacheKeyService;
use EgamiPeaks\Pizzazz\Services\PageCacheRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    $this->keyService = new PageCacheKeyService(new PageCacheRegistry);
});

describe('cache key generation', function () {
    it('generates correct key for home page', function () {
        $request = Request::create('/');

        $key = $this->keyService->getKey($request);

        expect($key)->toBe('pizzazz:page:path=home:query=0');
    });

    it('generates correct key for regular page', function () {
        $request = Request::create('/about');

        $key = $this->keyService->getKey($request);

        expect($key)->toBe('pizzazz:page:path=about:query=0');
    });

    it('generates correct key for nested page', function () {
        $request = Request::create('/blog/post-title');

        $key = $this->keyService->getKey($request);

        expect($key)->toBe('pizzazz:page:path=blog/post-title:query=0');
    });

    it('generates correct key with required query parameters', function () {
        Config::set('pizzazz.required_query_args', ['page', 'sort']);

        // Recreate service after config change
        $this->keyService = new PageCacheKeyService(new PageCacheRegistry);

        $request = Request::create('/posts?page=2&sort=date&ignore=this');

        $key = $this->keyService->getKey($request);
        $expectedQueryHash = md5('page=2&sort=date');

        expect($key)->toBe("pizzazz:page:path=posts:query=$expectedQueryHash");
    });

    it('ignores query parameters not in required list', function () {
        Config::set('pizzazz.required_query_args', ['page']);
        $this->keyService = new PageCacheKeyService(new PageCacheRegistry);

        $request = Request::create('/posts?page=1&utm_source=google&ref=twitter');

        $key = $this->keyService->getKey($request);
        $expectedQueryHash = md5('page=1');

        expect($key)->toBe("pizzazz:page:path=posts:query=$expectedQueryHash");
    });

    it('generates zero query hash when no required query args present', function () {
        Config::set('pizzazz.required_query_args', ['page']);
        $this->keyService = new PageCacheKeyService(new PageCacheRegistry);

        $request = Request::create('/posts?utm_source=google');

        $key = $this->keyService->getKey($request);

        expect($key)->toBe('pizzazz:page:path=posts:query=0');
    });

    it('sorts query parameters consistently', function () {
        Config::set('pizzazz.required_query_args', ['page', 'sort', 'category']);
        $this->keyService = new PageCacheKeyService(new PageCacheRegistry);

        $request1 = Request::create('/posts?sort=date&page=1&category=news');
        $request2 = Request::create('/posts?category=news&page=1&sort=date');

        $key1 = $this->keyService->getKey($request1);
        $key2 = $this->keyService->getKey($request2);

        expect($key1)->toBe($key2);
    });

    it('handles index.php path correctly', function () {
        $request = Request::create('/index.php');

        $key = $this->keyService->getKey($request);

        expect($key)->toBe('pizzazz:page:path=home:query=0');
    });
});

describe('page identifier generation', function () {
    it('generates correct page identifier', function () {
        $request = Request::create('/contact');

        $identifier = $this->keyService->getPageIdentifier($request);

        expect($identifier)->toBe(md5('/contact'));
    });

    it('generates same identifier for same path regardless of query parameters', function () {
        $request1 = Request::create('/posts?page=1');
        $request2 = Request::create('/posts?page=2');

        $identifier1 = $this->keyService->getPageIdentifier($request1);
        $identifier2 = $this->keyService->getPageIdentifier($request2);

        expect($identifier1)->toBe($identifier2);
    });
});

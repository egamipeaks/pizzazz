<?php

use EgamiPeaks\Pizzazz\Services\PageCacheKeyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;

beforeEach(function () {
    $this->keyService = new PageCacheKeyService;
});

describe('cache key generation', function () {
    it('generates correct key for home page', function () {
        $request = Request::create('/');

        $key = $this->keyService->getKey($request);

        expect($key)->toBe('path=home:query=0');
    });

    it('generates correct key for regular page', function () {
        $request = Request::create('/about');

        $key = $this->keyService->getKey($request);

        expect($key)->toBe('path=about:query=0');
    });

    it('generates correct key for nested page', function () {
        $request = Request::create('/blog/post-title');

        $key = $this->keyService->getKey($request);

        expect($key)->toBe('path=blog/post-title:query=0');
    });

    it('generates correct key with required query parameters', function () {
        Config::set('pizzazz.required_query_args', ['page', 'sort']);

        // Recreate service after config change
        $this->keyService = new PageCacheKeyService;

        $request = Request::create('/posts?page=2&sort=date&ignore=this');

        $key = $this->keyService->getKey($request);
        $expectedQueryHash = md5('page=2&sort=date');

        expect($key)->toBe("path=posts:query=$expectedQueryHash");
    });

    it('ignores query parameters not in required list', function () {
        Config::set('pizzazz.required_query_args', ['page']);
        $this->keyService = new PageCacheKeyService;

        $request = Request::create('/posts?page=1&utm_source=google&ref=twitter');

        $key = $this->keyService->getKey($request);
        $expectedQueryHash = md5('page=1');

        expect($key)->toBe("path=posts:query=$expectedQueryHash");
    });

    it('generates zero query hash when no required query args present', function () {
        Config::set('pizzazz.required_query_args', ['page']);
        $this->keyService = new PageCacheKeyService;

        $request = Request::create('/posts?utm_source=google');

        $key = $this->keyService->getKey($request);

        expect($key)->toBe('path=posts:query=0');
    });

    it('sorts query parameters consistently', function () {
        Config::set('pizzazz.required_query_args', ['page', 'sort', 'category']);
        $this->keyService = new PageCacheKeyService;

        $request1 = Request::create('/posts?sort=date&page=1&category=news');
        $request2 = Request::create('/posts?category=news&page=1&sort=date');

        $key1 = $this->keyService->getKey($request1);
        $key2 = $this->keyService->getKey($request2);

        expect($key1)->toBe($key2);
    });

    it('handles index.php path correctly', function () {
        $request = Request::create('/index.php');

        $key = $this->keyService->getKey($request);

        expect($key)->toBe('path=home:query=0');
    });
});

describe('cache tag generation', function () {
    it('generates correct tags for home page', function () {
        $request = Request::create('/');

        $tags = $this->keyService->getTags($request);

        expect($tags)->toBe(['page', 'page:'.md5('/')]);
    });

    it('generates correct tags for regular page', function () {
        $request = Request::create('/about');

        $tags = $this->keyService->getTags($request);

        expect($tags)->toBe(['page', 'page:'.md5('/about')]);
    });

    it('generates correct tags for nested page', function () {
        $request = Request::create('/blog/post-title');

        $tags = $this->keyService->getTags($request);

        expect($tags)->toBe(['page', 'page:'.md5('/blog/post-title')]);
    });
});

describe('page tag generation', function () {
    it('generates correct page tag', function () {
        $request = Request::create('/contact');

        $tag = $this->keyService->getPageTag($request);

        expect($tag)->toBe('page:'.md5('/contact'));
    });

    it('generates same tag for same path regardless of query parameters', function () {
        $request1 = Request::create('/posts?page=1');
        $request2 = Request::create('/posts?page=2');

        $tag1 = $this->keyService->getPageTag($request1);
        $tag2 = $this->keyService->getPageTag($request2);

        expect($tag1)->toBe($tag2);
    });
});

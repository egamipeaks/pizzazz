# Laravel page caching

[![Latest Version on Packagist](https://img.shields.io/packagist/v/egamipeaks/pizzazz.svg?style=flat-square)](https://packagist.org/packages/egamipeaks/pizzazz)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/egamipeaks/pizzazz/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/egamipeaks/pizzazz/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/egamipeaks/pizzazz/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/egamipeaks/pizzazz/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)

Pizzazz is a Laravel page caching package that provides intelligent, full-page HTTP caching for your Laravel applications. It automatically caches GET requests and serves cached responses with configurable cache invalidation, query parameter filtering, and authentication-aware caching.

The package includes middleware for automatic caching, cache flushing utilities, and comprehensive logging to help you optimize your application's performance.

## Installation

You can install the package via composer:

```bash
composer require egamipeaks/pizzazz
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="pizzazz-config"
```

This is the contents of the published config file:

```php
return [
    // Whether to enable Pizzazz
    'enabled' => env('PIZZAZZ_ENABLED', true),

    // Whether to enable debug mode
    'debug' => env('PIZZAZZ_DEBUG', false),

    // Add query vars that stop caching
    'disallowed_query_vars' => [],

    // Whether to cache authenticated requests
    'cache_authenticated_requests' => env('PIZZAZZ_CACHE_AUTHENTICATED_REQUESTS', false),

    // Minimum content length required to cache a page
    'min_content_length' => env('PIZZAZZ_MIN_CONTENT_LENGTH', 255),

    // Cache length in seconds. Default: 86400 seconds (1 day)
    'cache_length_in_seconds' => env('PIZZAZZ_CACHE_LENGTH', 86400),

    // Query arguments to include when caching
    'required_query_args' => [],
];
```

## Usage

### Basic Setup

After installation, add the page cache middleware to your routes or route groups:

```php
// In routes/web.php
Route::middleware('page-cache')->group(function () {
    Route::get('/', [HomeController::class, 'index']);
    Route::get('/about', [AboutController::class, 'index']);
    // Add more routes that should be cached
});
```

Or register the middleware globally in `app/Http/Kernel.php`:

```php
protected $middleware = [
    // ... other middleware
    \EgamiPeaks\Pizzazz\Middleware\PageCacheMiddleware::class,
];
```

### Configuration

Configure caching behavior in your `.env` file:

```bash
PIZZAZZ_ENABLED=true
PIZZAZZ_DEBUG=false
PIZZAZZ_CACHE_AUTHENTICATED_REQUESTS=false
PIZZAZZ_MIN_CONTENT_LENGTH=255
PIZZAZZ_CACHE_LENGTH=86400
```

### Advanced Usage

#### Programmatic Cache Control

```php
use EgamiPeaks\Pizzazz\Pizzazz;

// Check if a request can be cached
$pizzazz = app(Pizzazz::class);
$canCache = $pizzazz->canCache($request);

// Get cached content
$cachedContent = $pizzazz->getCache($request);
```

#### Cache Flushing

**Using Artisan Command**

Clear all page cache using the built-in artisan command:

```bash
php artisan pizzazz:flush
```

**Programmatically**

```php
use EgamiPeaks\Pizzazz\Services\PageCacheFlusher;

// Flush all cached pages
$flusher = app(PageCacheFlusher::class);
$flusher->flush();
```

#### Custom Query Parameters

Configure which query parameters should prevent caching:

```php
// In config/pizzazz.php
'disallowed_query_vars' => ['utm_source', 'utm_medium', 'debug'],
```

Or specify required query parameters to include in cache keys:

```php
// In config/pizzazz.php
'required_query_args' => ['locale', 'currency'],
```

### How It Works

1. **Automatic Caching**: The middleware automatically caches GET requests that return 200 responses
2. **Cache Keys**: Pages are cached using URL-based keys with optional query parameter filtering
3. **Cache Headers**: Cached responses include `X-Cache: HIT` header and `data-cached="true"` attribute on the body tag
4. **Smart Filtering**: Automatically skips caching for:
   - Non-GET requests
   - Authenticated users (unless configured otherwise)
   - Requests with disallowed query parameters
   - Responses shorter than the minimum content length
   - Non-200 HTTP responses

## Testing

```bash
./vendor/bin/pest
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Credits

- [Andrew Krzynowek](https://github.com/egamipeaks)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

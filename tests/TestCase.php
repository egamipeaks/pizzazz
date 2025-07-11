<?php

namespace EgamiPeaks\Pizzazz\Tests;

use EgamiPeaks\Pizzazz\ServiceProvider;
use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'EgamiPeaks\\Pizzazz\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            ServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
        config()->set('cache.default', 'array');

        // Set up pizzazz config defaults
        config()->set('pizzazz.enabled', true);
        config()->set('pizzazz.debug', false);
        config()->set('pizzazz.disallowed_query_vars', []);
        config()->set('pizzazz.cache_authenticated_requests', false);
        config()->set('pizzazz.min_content_length', 255);
        config()->set('pizzazz.cache_length_in_seconds', 86400);
        config()->set('pizzazz.required_query_args', []);

        /*
         foreach (\Illuminate\Support\Facades\File::allFiles(__DIR__ . '/database/migrations') as $migration) {
            (include $migration->getRealPath())->up();
         }
         */
    }
}

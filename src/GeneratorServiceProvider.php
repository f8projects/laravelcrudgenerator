<?php

namespace f8projects\laravelcrudgenerator;

use Illuminate\Support\ServiceProvider;

class GeneratorServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/LaravelCrudGeneratorStubs/' => resource_path('LaravelCrudGeneratorStubs')
        ], 'laravelcrudgeneratorstubs');
    }

    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('command.generator', function($app) {
            return $app['f8projects\laravelcrudgenerator\Commands\GeneratorCommand'];
        });
        $this->commands('command.generator');
    }
}

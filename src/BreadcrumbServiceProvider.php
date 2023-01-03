<?php

namespace Formfeed\Breadcrumbs;

use Formfeed\Breadcrumbs\Http\Middleware\InterceptBreadcrumbs;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;

class BreadcrumbServiceProvider extends ServiceProvider {
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {

        $this->addMiddleware();

        $this->publishes([
            __DIR__.'/../config/breadcrumbs.php' => config_path('breadcrumbs.php'),
        ], "breadcrumbs-config");
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
     //
    }

    public function addMiddleware()
    {
        $router = $this->app['router'];

        if ($router->hasMiddlewareGroup('nova')) {
            $router->pushMiddlewareToGroup('nova', InterceptBreadcrumbs::class);
            return;
        }

        if (! $this->app->configurationIsCached()) {
            config()->set('nova.middleware', array_merge(
                config('nova.middleware', []),
                [InterceptBreadcrumbs::class]
            ));
        }
    }
}

<?php

namespace Formfeed\Breadcrumbs;

use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Laravel\Nova\Events\ServingNova;
use Laravel\Nova\Nova;

class CardServiceProvider extends ServiceProvider {
    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot() {

        $this->publishes([
            __DIR__.'/../config/breadcrumbs.php' => config_path('breadcrumbs.php'),
        ], "breadcrumbs-config");

        Nova::serving(function (ServingNova $event) {
            Nova::script('breadcrumbs', __DIR__ . '/../dist/js/card.js');
            Nova::style('breadcrumbs', __DIR__ . '/../dist/css/card.css');
        });
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register() {
        //
    }
}

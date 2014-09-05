<?php namespace Heroicpixels\Filterable\Laravel;

/**
 * This file is part of the Heroicpixels/Filterable package for Laravel.
 *
 * @license http://opensource.org/licenses/MIT MIT
 */

use Illuminate\Support\ServiceProvider;

class FilterableServiceProvider extends ServiceProvider
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('Heroicpixels/filterable', 'Heroicpixels/filterable', __DIR__ . '/..');
    }

    public function register()
    {
        // ... If you would like custom methods, you can register the class here.
    }
}

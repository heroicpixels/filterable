<?php namespace Heroicpixels\Filterable;

use Illuminate\Support\ServiceProvider;

/**
 *	This file is part of the Heroicpixels/Filterable package for Laravel.
 *
 *	@license http://opensource.org/licenses/MIT MIT
 */

class FilterableServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

    /**
     * Bootstrap the application events.
     *
     * @return void
     */
    public function boot()
    {
        $this->package('heroicpixels/filterable');
    }

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		//
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array();
	}

}

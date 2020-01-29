<?php
namespace App\Providers;

use App\Http\Composers\CheckoutSubNavComposer;
use Illuminate\Support\ServiceProvider;

class ViewComposerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //Generate a list of months and future years for use on payment views
        view()->composer([
            'partials.checkout_subnav',
        ], CheckoutSubNavComposer::class);
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        //
    }
}

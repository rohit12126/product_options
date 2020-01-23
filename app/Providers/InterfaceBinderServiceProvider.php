<?php

namespace App\Providers;



use App\Core\Interfaces\AccountInterface;
use App\Core\Repositories\AccountRepository;
use App\Core\Interfaces\ApiDataInterface;
use App\Core\Interfaces\ContactInterface;
use App\Core\Repositories\ApiDataRepository;
use App\Core\Interfaces\InvoiceInterface;
use App\Core\Repositories\InvoiceRepository;
use App\Core\Interfaces\PricingGuideProductInterface;
use App\Core\Repositories\PricingGuideProductRepository;
use App\Core\Interfaces\UserInterface;
use App\Core\Repositories\UserRepository;
use App\Core\Interfaces\SiteInterface;
use App\Core\Repositories\SiteRepository;
use App\Core\Interfaces\JobCalculatorInterface;
use App\Core\Repositories\ContactRepository;
use App\Core\Repositories\JobCalculatorRepository;
use App\Core\Repositories\ProductOptionsRepository;
use App\Core\Interfaces\ProductOptionsInterface;
use App\Core\Repositories\PromotionRepository;
use App\Core\Interfaces\PromotionInterface;


use Illuminate\Support\ServiceProvider;

class InterfaceBinderServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {         
        $this->app->bind(
            AccountInterface::class,
            AccountRepository::class
        );
        
        $this->app->bind(
            ApiDataInterface::class,
            ApiDataRepository::class
        );

        $this->app->bind(
            InvoiceInterface::class,
            InvoiceRepository::class
        );

        $this->app->bind(
            UserInterface::class,
            UserRepository::class
        );

        $this->app->bind(
            SiteInterface::class,
            SiteRepository::class
        );

       
        $this->app->bind(
            PricingGuideProductInterface::class,
            PricingGuideProductRepository::class
        );

       
        $this->app->bind(
            JobCalculatorInterface::class,
            JobCalculatorRepository::class
        );
        $this->app->bind(
            ContactInterface::class,
            ContactRepository::class
        );

        $this->app->bind(
            ProductOptionsInterface::class,
            ProductOptionsRepository::class
        );

        $this->app->bind(
            PromotionInterface::class,
            PromotionRepository::class
        );
    }
}
<?php

namespace App\Providers;

use App\Models\PurchaseInvoice;
use App\Models\PurchaseReturn;
use App\Observers\PurchaseInvoiceObserver;
use App\Observers\PurchaseReturnObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        PurchaseInvoice::observe(PurchaseInvoiceObserver::class);
        PurchaseReturn::observe(PurchaseReturnObserver::class);
    }
}

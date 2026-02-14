<?php

namespace App\Providers;

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
        \App\Models\PurchaseInvoice::observe(\App\Observers\PurchaseInvoiceObserver::class);
        \App\Models\PurchaseReturn::observe(\App\Observers\PurchaseReturnObserver::class);
        \App\Models\SalesInvoice::observe(\App\Observers\SalesInvoiceObserver::class);
        \App\Models\SalesReturn::observe(\App\Observers\SalesReturnObserver::class);
        \App\Models\Receipt::observe(\App\Observers\ReceiptObserver::class);
        \App\Models\Payment::observe(\App\Observers\PaymentObserver::class);
        \App\Models\PayrollRun::observe(\App\Observers\PayrollRunObserver::class);
        \App\Models\Customer::observe(\App\Observers\CustomerObserver::class);
        \App\Models\Staff::observe(\App\Observers\StaffObserver::class);
        \App\Models\SalesAgent::observe(\App\Observers\SalesAgentObserver::class);
    }
}

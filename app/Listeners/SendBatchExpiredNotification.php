<?php

namespace App\Listeners;

use App\Events\BatchExpired;
use Illuminate\Support\Facades\Log;

class SendBatchExpiredNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(BatchExpired $event): void
    {
        $batch = $event->batch;
        
        // Log the expiration
        Log::warning("Batch expired: Product ID {$batch->product_id}, Batch {$batch->batch_number}, Quantity: {$batch->quantity_current}, Expiry Date: {$batch->expiry_date}");
        
        // TODO: Send notification to managers/admins
        // You can add email notifications, database notifications, etc. here
    }
}

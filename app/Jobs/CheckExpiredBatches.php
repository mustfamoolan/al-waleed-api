<?php

namespace App\Jobs;

use App\Models\InventoryBatch;
use App\Events\BatchExpired;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CheckExpiredBatches implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $today = Carbon::today();
        
        // Find all batches that expired today or before and still have quantity
        $expiredBatches = InventoryBatch::where('expiry_date', '<', $today)
            ->where('quantity_current', '>', 0)
            ->where('status', '!=', 'expired')
            ->get();

        foreach ($expiredBatches as $batch) {
            // Update status to expired
            $batch->status = 'expired';
            $batch->save();

            // Fire event
            event(new BatchExpired($batch));

            Log::info("Batch {$batch->id} marked as expired. Product: {$batch->product_id}, Quantity: {$batch->quantity_current}");
        }

        Log::info("Expired batches check completed. Found {$expiredBatches->count()} expired batches.");
    }
}

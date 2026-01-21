<?php

namespace App\Events;

use App\Models\InventoryBatch;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BatchExpired
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public InventoryBatch $batch;

    /**
     * Create a new event instance.
     */
    public function __construct(InventoryBatch $batch)
    {
        $this->batch = $batch;
    }
}

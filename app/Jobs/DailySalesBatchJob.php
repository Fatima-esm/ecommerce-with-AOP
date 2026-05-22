<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class DailySalesBatchJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
{
    $traceId = 'batch-' . uniqid();
    Log::channel('performance')->info("Starting daily sales batch processing", ['trace_id' => $traceId]);

    Order::whereDate('created_at', today()->subDay())
        ->chunkById(100, function ($orders) use ($traceId) {
            
            Log::channel('performance')->info("Processing chunk (100 records)", [
                'trace_id' => $traceId,
                'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB'
            ]);

            foreach ($orders as $order) {
                $order->update(['processed' => true]);
            }
        });

    Log::channel('performance')->info("finished processing daily sales batch", ['trace_id' => $traceId]);
}
}
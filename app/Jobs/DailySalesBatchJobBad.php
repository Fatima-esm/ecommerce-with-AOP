<?php

namespace App\Jobs;

use App\Models\Order;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DailySalesBatchJobBad
{
    public function handle()
    {
        $traceId = 'bad-batch-' . Str::uuid();
        
        $startMemory = memory_get_usage(true) / 1024 / 1024;

        Log::channel('performance')->warning('BAD Batch Job Started', [
            'trace_id' => $traceId,
            'aspect' => 'BadBatchAspect',
            'memory_start_mb' => round($startMemory, 2)
        ]);

        $orders = Order::whereDate('created_at', now()->subDay())->get();

        $afterLoadMemory = memory_get_usage(true) / 1024 / 1024;

        Log::channel('performance')->error('CRITICAL: Memory Spike Detected', [
            'trace_id' => $traceId,
            'orders_count' => $orders->count(),
            'memory_after_mb' => round($afterLoadMemory, 2),
            'increase_mb' => round($afterLoadMemory - $startMemory, 2),
            'note' => 'All records are now residing in RAM!'
        ]);

        foreach ($orders as $order) {
            Log::channel('performance')->info('Processing order (HEAVY)', [
                'trace_id' => $traceId,
                'order_id' => $order->id,
                'current_memory_mb' => round(memory_get_usage(true) / 1024 / 1024, 2)
            ]);
            
            $order->update(['processed' => true]);
        }

        Log::channel('performance')->info('BAD Batch Job Finished', [
            'trace_id' => $traceId,
            'total_memory_final' => round(memory_get_usage(true) / 1024 / 1024, 2)
        ]);
    }
}

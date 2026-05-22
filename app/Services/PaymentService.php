<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

class PaymentService
{
    
    public function process(Order $order)
    {
        Log::channel('performance')->info("Starting Synchronous Payment for Order #{$order->id}");

        sleep(5); 

        $success = rand(1, 100) <= 90; 

        if ($success) {
            $order->update(['status' => 'completed']);
            Log::channel('performance')->info("Payment Success for Order #{$order->id}");
        } else {
            $order->update(['status' => 'failed']);
            Log::channel('performance')->warning("Payment Failed for Order #{$order->id}");
        }

        return $success;
    }
}
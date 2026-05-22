<?php

namespace App\Jobs;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;



class ProcessPayment implements ShouldQueue

{

    use Dispatchable, InteractsWithQueue, SerializesModels;
    protected $order;
    public function __construct(Order $order)
    {

        $this->order = $order;

    }

    public function handle()
    {
        $traceId = 'job-' . Str::uuid();
        Log::channel('performance')->info('Async Job Started', [

            'aspect'   => 'AsyncProcessingAspect',
            'trace_id' => $traceId,
            'job'      => 'ProcessPayment',
            'order_id' => $this->order->id,

        ]);

        try {
            $paymentSuccess = $this->fakePaymentGateway();
            if ($paymentSuccess) {
                $this->order->update(['status' => 'completed']);
                Log::channel('performance')->info("Order #{$this->order->id} completed successfully.");
            } else {
                DB::transaction(function () {
                    foreach ($this->order->items as $item) {
                        Product::where('id', $item->product_id)
                            ->increment('stock_quantity', $item->quantity);
                    }
                    $this->order->update(['status' => 'cancelled']);
                });
                Log::channel('performance')->warning("Payment failed for order #{$this->order->id}. Stock restored.");
            }

            Log::channel('performance')->info('Async Job Completed', [
                'aspect'   => 'AsyncProcessingAspect',
                'trace_id' => $traceId,
                'job'      => 'ProcessPayment',
                'order_id' => $this->order->id,
                'status'   => $this->order->status,
                'success'  => $paymentSuccess,

            ]);



        } catch (\Exception $e) {

            Log::channel('performance')->error('Async Job Failed', [
                'aspect'   => 'AsyncProcessingAspect',
                'trace_id' => $traceId,
                'order_id' => $this->order->id,
                'error'    => $e->getMessage(),
            ]);

            throw $e;

        }

    }



    private function fakePaymentGateway()

    {

        return rand(1, 100) <= 90;

    }

} 
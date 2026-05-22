<?php

namespace App\Http\Controllers;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use App\Jobs\ProcessPayment;
use App\Jobs\ProcessPaymentSync;
use App\Services\PaymentService;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Cart;
use App\Models\Order;

class OrderController extends Controller
{

    //all myOrders
    public function allOrders(Request $request)
    {
        $orders = $request->user()->orders()->with('items.product')->get();
        return response()->json($orders);
    }

    //show order details
    public function show(Request $request, $id)
    {
        $order = $request->user()->orders()->with('items.product')->find($id);
        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }
        return response()->json($order);
    }

    //----------------------------------------------------------------------------

    //bad code
    //before create order to check stock and create order
    
    public function befor_createOrder(Request $request) {
        $traceId = $request->header('X-Trace-Id') ?? 'N/A';

        $user = $request->user();
        
        $cart = $user->cart()->with('items.product')->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'الأسف، سلتك فارغة'], 400);
        }

        $totalAmount = 0;
        $orderItemsData = [];

        foreach ($cart->items as $item) {
            $product = \App\Models\Product::find($item->product_id);

            if ($product->stock_quantity < $item->quantity) {
                return response()->json([
                    'message' => "المنتج {$product->name} غير متوفر بالكمية المطلوبة."
                ], 400);
            }

            \Illuminate\Support\Facades\Log::channel('performance')->warning('Unsafe Stock Update', [
                'aspect'   => 'UnsafeAspect',
                'trace_id' => $traceId,
                'product_id' => $product->id
            ]);

            $product->stock_quantity -= $item->quantity;
            $product->save();

            $totalAmount += $item->quantity * $item->price;
            $orderItemsData[] = [
                'product_id' => $product->id,
                'quantity'   => $item->quantity,
                'price'      => $item->price,
            ];
        }

        $order = $user->orders()->create([
            'total_amount' => $totalAmount,
            'status'       => 'pending'
        ]);

        $order->items()->createMany($orderItemsData);
        $cart->items()->delete();

        $paymentService = new \App\Services\PaymentService();
        $paymentService->process($order);

        return response()->json([
            'message' => 'تم إنشاء الطلب ومعالجة الدفع بنجاح',
            'order_id' => $order->id,
            'status' => $order->status
        ], 201);
    }

    //-----------------------------------------------------------------------
    //after update
    //to create order
    public function createOrder(Request $request) {
        $user = $request->user();
        $cart = $user->cart()->with('items.product')->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        $order = DB::transaction(function () use ($cart, $user) {
            $totalAmount = 0;
            $orderItemsData = [];

            foreach ($cart->items as $item) {
                // Pessimistic Locking + AOP Logging
                $product = Product::where('id', $item->product_id)
                                ->lockForUpdate()
                        ->firstOrFail();

                if ($product->stock_quantity < $item->quantity) {
                    throw new \Exception("Insufficient stock for product: {$product->name}");
                }

                $oldStock = $product->stock_quantity;

                $product->decrement('stock_quantity', $item->quantity);
                $product->increment('version');   // للـ Optimistic في المستقبل

                Log::channel('performance')->info('Stock Updated - Race Condition Protected', [
                    'aspect'      => 'RaceConditionAspect',
                    'trace_id'    => request()->header('X-Trace-Id') ?? 'N/A',
                    'product_id'  => $product->id,
                    'old_stock'   => $oldStock,
                    'new_stock'   => $product->stock_quantity,
                    'version'     => $product->version,
                ]);

                $totalAmount += $item->quantity * $item->price;
                $orderItemsData[] = [
                    'product_id' => $product->id,
                    'quantity'   => $item->quantity,
                    'price'      => $item->price,
                ];
            }

            $order = $user->orders()->create([
                'total_amount' => $totalAmount,
                'status' => 'pending'
            ]);

            $order->items()->createMany($orderItemsData);
            $cart->items()->delete();

            return $order;
        });


        // Asynchronous
        ProcessPayment::dispatch($order);

        return response()->json([
            'message' => 'Order placed successfully - Payment processing in background',
            'order_id' => $order->id
        ], 201);
    }



    //cancel order if payment status is pending
    public function cancel(Request $request, $id)
    {
        $order = $request->user()->orders()->find($id);
        if (!$order || $order->status !== 'pending') {
            return response()->json(['message' => 'Order cannot be cancelled'], 400);
        }

        DB::transaction(function () use ($order) {
            // إعادة المخزون
            foreach ($order->items as $item) {
                Product::where('id', $item->product_id)
                    ->increment('stock_quantity', $item->quantity);
            }
            $order->update(['status' => 'cancelled']);
        });

        return response()->json(['message' => 'Order cancelled and stock restored']);
    }


}

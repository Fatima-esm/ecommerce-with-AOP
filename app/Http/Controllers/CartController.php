<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use App\Models\CartItem;
class CartController extends Controller
{


    //to view the cart
    public function view_cart(Request $request) {

        $user = $request->user();
        $cart = $user->cart()->with('items.product')->first();

        if (!$cart) {
            return response()->json([
                'message' => 'Your cart is empty',
                'items' => [],
                'total' => 0
            ]);
        }

        $total = $cart->items->sum(function ($item) {
            return $item->quantity * $item->price;
        });

        return response()->json([
            'cart_id' => $cart->id,
            'items' => $cart->items,
            'total' => $total
        ]);   
     }



        //to add a product to the cart
    public function add_to_cart(Request $request) {
            $request->validate([
                'product_id' => 'required|exists:products,id',
                'quantity'   => 'required|integer|min:1',
            ]);

            $user = $request->user();
            
            $cart = $user->cart()->firstOrCreate(['user_id' => $user->id]);
            
            $cartItem = CartItem::where('cart_id', $cart->id)
                                ->where('product_id', $request->product_id)
                                ->first();
            
            $product = Product::findOrFail($request->product_id);
            
            if ($cartItem) {
                $cartItem->increment('quantity', $request->quantity);
            } else {
                CartItem::create([
                    'cart_id'    => $cart->id,
                    'product_id' => $request->product_id,
                    'quantity'   => $request->quantity,
                    'price'      => $product->price,
                ]);
            }
            
            return response()->json(['message' => 'Product added to cart']);
    }

          //to add a product to the cart
    public function befor_add_to_cart(Request $request) {

        \Log::info('Request started at: ' . microtime(true));

        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity'   => 'required|integer|min:1',
        ]);

        $user = $request->user();
        
        $cart = $user->cart()->firstOrCreate(['user_id' => $user->id]);
        
        $cartItem = CartItem::where('cart_id', $cart->id)
                            ->where('product_id', $request->product_id)
                            ->first();
        
        $product = Product::findOrFail($request->product_id);
        
        $product->stock_quantity = $product->stock_quantity - $request->quantity;
        $product->save();  

        
        if ($cartItem) {
            $cartItem->increment('quantity', $request->quantity);
        } else {
            CartItem::create([
                'cart_id'    => $cart->id,
                'product_id' => $request->product_id,
                'quantity'   => $request->quantity,
                'price'      => $product->price,
            ]);
        }

        return response()->json(['message' => 'Product added to cart']);
    }


    //to delete a product from the cart
    public function delete_from_cart(CartItem $cartItem) { 
        
       if ($cartItem->cart->user_id !== auth()->id()) {
        return response()->json(['message' => 'Unauthorized'], 403);
    }
    
    $cartItem->delete();
    
    return response()->json(['message' => 'Item removed from cart']);



    }


}
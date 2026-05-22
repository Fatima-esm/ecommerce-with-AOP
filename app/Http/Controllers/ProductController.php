<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    //to browse the products with pagination for 20 products per page
    public function index() {
        $products = Product::paginate(20);
        return response()->json($products);
    }


    public function show(Product $product) {
        return response()->json($product);
    }




}
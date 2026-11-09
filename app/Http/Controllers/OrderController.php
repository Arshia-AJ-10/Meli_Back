<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function index(Request $req)
    {
        $orders = Cart::where('user_id', $req->user()->id)
            ->where('status', 'paid')
            ->with('products')
            ->orderBy('id', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'orders' => $orders->items(),
        ], 200);
    }

    public function det(Request $req, $id)
    {
        $order = Cart::where('user_id', $req->user()->id)
            ->where('status', 'paid')
            ->with('products')
            ->find($id);

        if (!$order) {
            return response()->json([
                'success' => false,
                'message' => 'سفارش موردنظر پیدا نشد.'
            ], 404);
        }

        $total = $order->products->sum(function ($product) {
            return $product->pivot->price * $product->pivot->quantity;
        });

        return response()->json([
            'success' => true,
            'order' => $order,
            'total' => $total
        ], 200);
    }

    public function buy(Request $req)
    {
        $cart = Cart::where('user_id', $req->user()->id)
            ->where('status', 'active')
            ->with('products')
            ->first();

        if (!$cart || $cart->products->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'سبد خرید شما خالی است.'
            ], 400);
        }

        $cart->status = 'paid';
        $cart->save();

        return response()->json([
            'success' => true,
            'message' => 'سفارش شما با موفقیت ثبت شد.',
            'order' => $cart
        ], 200);
    }
}
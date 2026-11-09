<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartProduct;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CartController extends Controller
{
    // نمایش سبد خرید
    public function index(Request $req)
    {
        $cart = Cart::where('user_id', $req->user()->id)
            ->where('status', 'active')
            ->with('products')
            ->firstOrCreate();

        $cart->load('products');

        $total = $cart->products->sum(function ($product) {
            return $product->pivot->price * $product->pivot->quantity;
        });

        return response()->json([
            'success' => true,
            'cart' => $cart,
            'total' => $total
        ], 200);
    }

    // افزودن محصول به سبد خرید
    public function add(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1'
        ], [
            'product_id.required' => 'انتخاب محصول الزامی است.',
            'product_id.exists' => 'محصول موردنظر وجود ندارد.',
            'quantity.required' => 'تعداد محصول الزامی است.',
            'quantity.integer' => 'تعداد باید عدد صحیح باشد.',
            'quantity.min' => 'تعداد محصول باید حداقل یک عدد باشد.'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::findOrFail($req->product_id);

        $cart = Cart::firstOrCreate([
            'user_id' => $req->user()->id,
            'status' => 'active'
        ]);

        DB::beginTransaction();

        try {
            $item = CartProduct::where('cart_id', $cart->id)
                ->where('product_id', $product->id)
                ->first();

            if ($item) {
                $item->quantity += $req->quantity;
                $item->save();
            } else {
                CartProduct::create([
                    'cart_id' => $cart->id,
                    'product_id' => $product->id,
                    'price' => $product->price,
                    'quantity' => $req->quantity
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'محصول با موفقیت به سبد خرید اضافه شد.'
            ], 200);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'خطا در افزودن محصول به سبد خرید.'
            ], 500);
        }
    }

    // حذف محصول از سبد خرید
    public function delete(Request $req, $itemId)
    {
        $cart = Cart::where('user_id', $req->user()->id)
            ->where('status', 'active')
            ->first();

        if (!$cart) {
            return response()->json([
                'success' => false,
                'message' => 'سبد خریدی پیدا نشد.'
            ], 404);
        }

        $item = CartProduct::where('id', $itemId)
            ->where('cart_id', $cart->id)
            ->first();

        if (!$item) {
            return response()->json([
                'success' => false,
                'message' => 'محصول موردنظر در سبد خرید شما وجود ندارد.'
            ], 404);
        }

        $item->delete();

        return response()->json([
            'success' => true,
            'message' => 'محصول با موفقیت از سبد خرید حذف شد.'
        ], 200);
    }
}
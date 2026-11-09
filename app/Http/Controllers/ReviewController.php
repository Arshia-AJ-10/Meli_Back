<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\UserProduct;
use App\Models\Cart;
use App\Models\CartProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ReviewController extends Controller
{
    public function rate(Request $req, $id)
    {
        $product = Product::findOrFail($id);

        $validator = Validator::make($req->all(), [
            'rating' => 'required|integer|min:1|max:5',
            'text' => 'nullable|string|max:2000',
        ], [
            'rating.required' => 'ثبت امتیاز الزامی است.',
            'rating.integer' => 'امتیاز باید عدد صحیح باشد.',
            'rating.min' => 'حداقل امتیاز ۱ است.',
            'rating.max' => 'حداکثر امتیاز ۵ است.',
            'text.string' => 'متن نظر باید رشته باشد.',
            'text.max' => 'متن نظر نباید بیشتر از ۲۰۰۰ کاراکتر باشد.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $orders = Cart::where('user_id', $req->user()->id)
            ->where('status', 'paid')
            ->get();

        $purchased = false;

        foreach ($orders as $order) {
            $item = CartProduct::where('cart_id', $order->id)
                ->where('product_id', $product->id)
                ->first();

            if ($item) {
                $purchased = true;
                break;
            }
        }

        if (!$purchased) {
            return response()->json([
                'success' => false,
                'message' => 'فقط خریداران این محصول می‌توانند نظر ثبت کنند.'
            ], 403);
        }

        $review = UserProduct::where('user_id', $req->user()->id)
            ->where('product_id', $product->id)
            ->first();

        if ($review) {
            $review->rating = $req->rating;
            $review->text = $req->text;
            $review->save();
        } else {
            UserProduct::create([
                'user_id' => $req->user()->id,
                'product_id' => $product->id,
                'rating' => $req->rating,
                'text' => $req->text
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'نظر و امتیاز با موفقیت ثبت شد.'
        ], 200);
    }

    public function index($id)
    {
        $product = Product::findOrFail($id);

        $reviews = UserProduct::where('product_id', $product->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        $averageRating = UserProduct::where('product_id', $product->id)
            ->avg('rating') ?? 0;

        return response()->json([
            'success' => true,
            'reviews' => $reviews->items(),
            'average_rating' => round($averageRating, 2),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total()
            ]
        ], 200);
    }
}
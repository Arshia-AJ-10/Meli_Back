<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\UserProduct;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ProductController extends Controller
{
    public function list(Request $req)
    {
        $query = Product::query();

        if ($req->filled('search')) {
            $query->where('name', 'LIKE', '%' . $req->search . '%');
        }

        if ($req->filled('category_id')) {
            $query->where('categoray_id', $req->category_id);
        }

        if ($req->filled('min_price')) {
            $query->where('price', '>=', $req->min_price);
        }

        if ($req->filled('max_price')) {
            $query->where('price', '<=', $req->max_price);
        }

        $query->withAvg('users as average_rating', 'user_products.rating');

        $products = $query->paginate(10);

        return response()->json([
            'data' => $products->items(),
            'meta' => [
                'current_page' => $products->currentPage(),
                'last_page' => $products->lastPage(),
                'total' => $products->total()
            ]
        ], 200);
    }
    public function crud()
    {
        $products = Product::withTrashed()->orderBy('id', 'desc')->get();

        return response()->json([
            'success' => true,
            'products' => $products
        ], 200);
    }

    public function store(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'name' => 'required|string|max:255',
            'img' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'price' => 'required|numeric|min:0',
            'categoray_id' => 'required|exists:categorays,id',
        ], [
            'name.required' => 'وارد کردن نام محصول الزامی است.',
            'img.image' => 'فایل انتخاب‌شده باید تصویر باشد.',
            'img.mimes' => 'فرمت تصویر باید jpg، jpeg، png یا webp باشد.',
            'img.max' => 'حجم تصویر نباید بیشتر از ۵ مگابایت باشد.',
            'price.required' => 'وارد کردن قیمت محصول الزامی است.',
            'price.numeric' => 'قیمت باید عددی باشد.',
            'categoray_id.required' => 'انتخاب دسته‌بندی الزامی است.',
            'categoray_id.exists' => 'دسته‌بندی انتخاب‌شده معتبر نیست.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        $image = null;
        if ($req->hasFile('img')) {
            $image = $req->file('img')->store('products', 'public');
        }
        $product = new Product();
        $product->name = $req->name;
        $product->img = $image;
        $product->price = $req->price;
        $product->categoray_id = $req->categoray_id;
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'محصول با موفقیت ثبت شد.',
            'product' => $product
        ], 201);
    }
    public function edit(Request $req, $id)
    {
        $validator = Validator::make($req->all(), [
            'name' => 'required|string|max:255',
            'img' => 'nullable|string|max:255',
            'price' => 'required|numeric|min:0',
            'categoray_id' => 'required|exists:categorays,id',
        ], [
            'name.required' => 'وارد کردن نام محصول الزامی است.',
            'price.required' => 'وارد کردن قیمت محصول الزامی است.',
            'price.numeric' => 'قیمت باید عددی باشد.',
            'categoray_id.required' => 'انتخاب دسته‌بندی الزامی است.',
            'categoray_id.exists' => 'دسته‌بندی انتخاب‌شده معتبر نیست.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $product = Product::findOrFail($id);
        $product->name = $req->name;
        if ($req->hasFile('img')) {
            if ($product->img && Storage::disk('public')->exists($product->img)) {
                Storage::disk('public')->delete($product->img);
            }

            $product->img = $req->file('img')->store('products', 'public');
        }
        $product->price = $req->price;
        $product->categoray_id = $req->categoray_id;
        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'محصول با موفقیت ویرایش شد.',
            'product' => $product
        ], 200);
    }
    public function det($id)
    {
        $product = Product::findOrFail($id);
        $averageRating = $product->users()->avg('rating');
        $product['average_rating'] = $averageRating;
        return response()->json([
            'success' => true,
            'product' => $product
        ], 200);
    }
    public function delete($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'محصول با موفقیت حذف شد.',
            'product' => $product
        ], 200);
    }
}

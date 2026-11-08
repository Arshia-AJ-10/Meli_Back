<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Categoray;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class CategoryController extends Controller
{
    public function list()
    {
        $categoray = Categoray::withTrashed()->orderBy('order')->get();
        return response()->json([
            'success' => true,
            'categorays' => $categoray
        ]);
    }
    public function store(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'name' => 'required|string|max:255',
        ], [
            'name.required' => 'وارد کردن نام دسته‌بندی الزامی است.',
            'name.string' => 'نام دسته‌بندی باید متن باشد.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $order = Categoray::max('order') ?? 0;

        $cat = new Categoray();
        $cat->name = $req->name;
        $cat->order = $order + 1;
        $cat->save();

        return response()->json([
            'success' => true,
            'categoray' => $cat
        ], 201);
    }
    public function edit(Request $req, $id)
    {
        $validator = Validator::make($req->all(), [
            'name' => 'required|string|max:255',
            'order' => 'required|integer|min:1',
        ], [
            'name.required' => 'وارد کردن نام دسته‌بندی الزامی است.',
            'name.string' => 'نام دسته‌بندی باید متن باشد.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $cat = Categoray::findOrFail($id);
        $cat->name = $req->name;
        $cat->order = $req->order;
        $cat->save();

        return response()->json([
            'success' => true,
            'categoray' => $cat
        ], 201);
    }
    public function delete($id)
    {
        $cat = Categoray::findOrFail($id);
        $cat->delete();

        return response()->json([
            'success' => true,
            'message' => 'دسته‌بندی با موفقیت حذف شد.',
            'categoray' => $cat
        ], 200);
    }

    public function reorder(Request $req)
    {
        $validator = Validator::make($req->all(), [
            'order' => 'required|array|min:1',
            'order.*' => 'required|integer|distinct|exists:categorays,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();

        try {
            foreach ($req->order as $index => $id) {
                $cat = Categoray::findOrFail($id);
                $cat->order = $index + 1;
                $cat->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'ترتیب دسته‌بندی‌ها با موفقیت تغییر کرد.'
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'خطا در تغییر ترتیب دسته‌بندی‌ها.'
            ], 500);
        }
    }
}

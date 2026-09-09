<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\FoodItem;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    /**
     * Return categories and food items for the authenticated user's store.
     *
     * Both models carry the `#[ScopedBy(StoreScope::class)]` global scope, so
     * the authenticated user's store_id automatically constrains the result.
     * This exact endpoint is consumed by the web POS (Phase 1) and the Flutter
     * app (Phase 2).
     *
     * @return array<string, mixed>
     */
    public function index(Request $request)
    {
        $store = $request->user()->store()->first();

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $foodItems = FoodItem::query()
            ->with('category')
            ->where('is_available', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return [
            'store' => $store ? [
                'id' => $store->id,
                'name' => $store->name,
                'phone' => $store->phone,
                'address' => $store->address,
                'city' => $store->city,
                'state' => $store->state,
                'pincode' => $store->pincode,
                'gstin' => $store->gstin,
                'fssai_license' => $store->fssai_license,
                'upi_vpa' => $store->upi_vpa,
                'currency' => $store->currency ?? 'INR',
            ] : null,
            'categories' => $categories->map(fn (Category $category) => [
                'id' => $category->id,
                'uuid' => $category->uuid,
                'name' => $category->name,
                'sort_order' => $category->sort_order ?? 0,
            ]),
            'items' => $foodItems->map(fn (FoodItem $item) => [
                'id' => $item->id,
                'uuid' => $item->uuid,
                'category_id' => $item->category_id,
                'name' => $item->name,
                'price' => $item->price,
                'food_type' => $item->food_type?->value ?? 'veg',
                'gst_rate' => $item->gst_rate ?? 5,
                'sort_order' => $item->sort_order ?? 0,
            ]),
        ];
    }
}

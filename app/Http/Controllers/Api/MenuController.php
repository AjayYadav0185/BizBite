<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\FoodItem;
use App\Support\StorePayload;
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
            // Single canonical store shape (App\Support\StorePayload) — the
            // same keys `POST /api/login` and `GET /api/store` return, so the
            // Flutter StoreProfileModel parses one contract everywhere.
            'store' => StorePayload::for($store),
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

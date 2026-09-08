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
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $foodItems = FoodItem::query()
            ->with('category')
            ->where('is_available', true)
            ->orderBy('name')
            ->get();

        return [
            'categories' => $categories->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
            ]),
            'items' => $foodItems->map(fn (FoodItem $item) => [
                'id' => $item->id,
                'category_id' => $item->category_id,
                'name' => $item->name,
                'price' => $item->price,
            ]),
        ];
    }
}

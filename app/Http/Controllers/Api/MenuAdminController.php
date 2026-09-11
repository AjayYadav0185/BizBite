<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\FoodItem;
use App\Services\Audit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

// Admin-only menu mutations for the Flutter Store console.
// GET /api/menu stays readable by admin+cashier; every write here is
// role:admin (routes/api.php) + StoreScope tenant-scoped. Validation
// mirrors Livewire Admin MenuManager so web + mobile match.
final class MenuAdminController extends Controller
{
    public function storeCategory(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:60'],
        ]);
        $name = trim($payload['name']);
        $category = Category::create([
            'store_id' => $request->user()->store_id,
            'name' => $name,
            'is_active' => true,
        ]);
        Audit::record(
            $request->user(),
            AuditLog::ACTION_CATEGORY_CREATED,
            'Category "'.$name.'" created (mobile).',
            entityType: 'category',
            entityId: $category->id,
            entityName: $name,
            new: ['name' => $name]
        );

        return response()->json([
            'message' => 'Category added.',
            'category' => [
                'id' => $category->id,
                'uuid' => $category->uuid,
                'name' => $category->name,
            ],
        ], 201);
    }

    public function updateCategory(Request $request, int $id): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:60'],
        ]);
        $category = Category::findOrFail($id);
        $oldName = $category->name;
        $newName = trim($payload['name']);
        $category->update(['name' => $newName]);
        Audit::record(
            $request->user(),
            AuditLog::ACTION_CATEGORY_UPDATED,
            'Category renamed (mobile).',
            entityType: 'category',
            entityId: $category->id,
            entityName: $newName,
            old: ['name' => $oldName],
            new: ['name' => $newName]
        );

        return response()->json([
            'message' => 'Category renamed.',
            'category' => ['id' => $category->id, 'name' => $category->name],
        ]);
    }

    public function storeItem(Request $request): JsonResponse
    {
        $payload = $request->validate([
            'category_id' => ['required', 'integer'],
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'price' => ['required', 'numeric', 'min:0.5', 'max:999999'],
        ]);
        Category::findOrFail((int) $payload['category_id']);
        $item = FoodItem::create([
            'store_id' => $request->user()->store_id,
            'category_id' => (int) $payload['category_id'],
            'name' => trim($payload['name']),
            'price' => round((float) $payload['price'], 2),
            'is_available' => true,
        ]);
        Audit::record(
            $request->user(),
            AuditLog::ACTION_ITEM_CREATED,
            'Item "'.$item->name.'" created (mobile).',
            entityType: 'food_item',
            entityId: $item->id,
            entityName: $item->name,
            new: [
                'name' => $item->name,
                'price' => (string) $item->price,
                'category_id' => $item->category_id,
            ],
            amount: (string) $item->price
        );

        return response()->json([
            'message' => 'Item added.',
            'item' => [
                'id' => $item->id,
                'uuid' => $item->uuid,
                'category_id' => $item->category_id,
                'name' => $item->name,
                'price' => $item->price,
            ],
        ], 201);
    }

    public function updateItem(Request $request, int $id): JsonResponse
    {
        $payload = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:80'],
            'price' => ['required', 'numeric', 'min:0.5', 'max:999999'],
            'category_id' => ['required', 'integer'],
        ]);
        Category::findOrFail((int) $payload['category_id']);
        $item = FoodItem::findOrFail($id);
        $oldName = $item->name;
        $oldPrice = (string) $item->price;
        $oldCategoryId = $item->category_id;
        $newName = trim($payload['name']);
        $price = number_format(round((float) $payload['price'], 2), 2, '.', '');
        $item->update([
            'name' => $newName,
            'price' => $price,
            'category_id' => (int) $payload['category_id'],
        ]);
        if (bccomp($oldPrice, $price, 2) !== 0) {
            Audit::record(
                $request->user(),
                AuditLog::ACTION_PRICE_UPDATED,
                'Price changed (mobile).',
                entityType: 'food_item',
                entityId: $item->id,
                entityName: $newName,
                old: ['price' => $oldPrice],
                new: ['price' => $price],
                amount: $price
            );
        }
        if ($oldName !== $newName || (int) $oldCategoryId !== (int) $payload['category_id']) {
            Audit::record(
                $request->user(),
                AuditLog::ACTION_ITEM_CREATED,
                'Item updated (mobile).',
                entityType: 'food_item',
                entityId: $item->id,
                entityName: $newName,
                old: ['name' => $oldName, 'category_id' => $oldCategoryId],
                new: ['name' => $newName, 'category_id' => (int) $payload['category_id']]
            );
        }

        return response()->json([
            'message' => 'Item updated.',
            'item' => [
                'id' => $item->id,
                'category_id' => $item->category_id,
                'name' => $item->name,
                'price' => $item->price,
            ],
        ]);
    }

    public function destroyItem(Request $request, int $id): JsonResponse
    {
        $item = FoodItem::findOrFail($id);
        $name = $item->name;
        $price = (string) $item->price;
        $item->delete();
        Audit::record(
            $request->user(),
            AuditLog::ACTION_ITEM_DELETED,
            'Item deleted (mobile).',
            entityType: 'food_item',
            entityId: $id,
            entityName: $name,
            old: ['name' => $name, 'price' => $price],
            amount: $price
        );

        return response()->json(['message' => 'Item deleted.']);
    }
}


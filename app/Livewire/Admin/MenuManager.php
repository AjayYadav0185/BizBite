<?php

namespace App\Livewire\Admin;

use App\Models\Category;
use App\Models\FoodItem;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

/**
 * OWNER ADMIN PORTAL — Menu Management.
 *
 * Instant, inline CRUD for Categories and FoodItems with per-field Livewire
 * validation. Every mutation is a Livewire action; the data table re-renders
 * reactively without a page refresh. Tenancy is enforced structurally: both
 * models carry the global StoreScope, and new rows are stamped with the
 * authenticated admin's `store_id`, so a tenant can never read or mutate
 * another store's menu.
 */
#[Layout('layouts.app')]
final class MenuManager extends Component
{
    // -----------------------------------------------------------------
    // Category state
    // -----------------------------------------------------------------

    #[Validate('required|min:2|max:60')]
    public string $newCategoryName = '';

    public ?int $editingCategoryId = null;

    public string $editingCategoryName = '';

    // -----------------------------------------------------------------
    // Food item state
    // -----------------------------------------------------------------

    /** Draft for the "add item" row. */
    public array $newItem = [
        'category_id' => null,
        'name' => '',
        'price' => null,
    ];

    public ?int $editingItemId = null;

    public array $editingItem = [
        'name' => '',
        'price' => null,
        'category_id' => null,
    ];

    public function mount(): void
    {
        $this->authorize('manage-menu');
    }

    #[Computed]
    public function categories(): Collection
    {
        return Category::query()
            ->with(['foodItems' => fn ($query) => $query->orderBy('name')])
            ->orderBy('name')
            ->get();
    }

    // -----------------------------------------------------------------
    // Category actions
    // -----------------------------------------------------------------

    public function createCategory(): void
    {
        $this->validateOnly('newCategoryName', [
            'newCategoryName' => ['required', 'min:2', 'max:60'],
        ]);

        Category::create([
            'store_id' => auth()->user()->store_id,
            'name' => trim($this->newCategoryName),
            'is_active' => true,
        ]);

        $this->reset('newCategoryName');
    }

    public function editCategory(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);

        $this->editingCategoryId = $category->id;
        $this->editingCategoryName = $category->name;
        $this->resetErrorBag('editingCategoryName');
    }

    public function saveCategory(): void
    {
        $this->validate([
            'editingCategoryName' => ['required', 'min:2', 'max:60'],
        ]);

        Category::whereKey($this->editingCategoryId)->update([
            'name' => trim($this->editingCategoryName),
        ]);

        $this->cancelCategoryEdit();
    }

    public function cancelCategoryEdit(): void
    {
        $this->reset('editingCategoryId', 'editingCategoryName');
    }

    public function toggleCategory(int $categoryId): void
    {
        $category = Category::findOrFail($categoryId);
        $category->update(['is_active' => ! $category->is_active]);
    }

    public function deleteCategory(int $categoryId): void
    {
        $category = Category::withCount('foodItems')->findOrFail($categoryId);

        // Guard: never silently orphan the store's food items.
        if ($category->food_items_count > 0) {
            $this->addError('newCategoryName',
                "Category '{$category->name}' still has {$category->food_items_count} item(s). Move or delete them first."
            );

            return;
        }

        $category->delete();
    }

    // -----------------------------------------------------------------
    // Food item actions
    // -----------------------------------------------------------------

    public function createItem(): void
    {
        $this->validate([
            'newItem.category_id' => ['required', 'integer'],
            'newItem.name' => ['required', 'min:2', 'max:80'],
            'newItem.price' => ['required', 'numeric', 'min:0.5', 'max:999999'],
        ], [], [
            'newItem.category_id' => 'category',
            'newItem.name' => 'item name',
            'newItem.price' => 'price',
        ]);

        // Guard against injecting another store's category id: the lookup
        // below is constrained by the global StoreScope automatically.
        Category::findOrFail($this->newItem['category_id']);

        FoodItem::create([
            'store_id' => auth()->user()->store_id,
            'category_id' => (int) $this->newItem['category_id'],
            'name' => trim($this->newItem['name']),
            'price' => round((float) $this->newItem['price'], 2),
            'is_available' => true,
        ]);

        $this->reset('newItem');
    }

    public function toggleItem(int $itemId): void
    {
        $item = FoodItem::findOrFail($itemId);
        $item->update(['is_available' => ! $item->is_available]);
    }

    public function editItem(int $itemId): void
    {
        $item = FoodItem::findOrFail($itemId);

        $this->editingItemId = $item->id;
        $this->editingItem = [
            'name' => $item->name,
            'price' => (string) $item->price,
            'category_id' => $item->category_id,
        ];
    }

    public function saveItem(): void
    {
        $this->validate([
            'editingItem.name' => ['required', 'min:2', 'max:80'],
            'editingItem.price' => ['required', 'numeric', 'min:0.5', 'max:999999'],
            'editingItem.category_id' => ['required', 'integer'],
        ], [], [
            'editingItem.name' => 'item name',
            'editingItem.price' => 'price',
            'editingItem.category_id' => 'category',
        ]);

        Category::findOrFail($this->editingItem['category_id']);

        FoodItem::whereKey($this->editingItemId)->update([
            'name' => trim($this->editingItem['name']),
            'price' => round((float) $this->editingItem['price'], 2),
            'category_id' => (int) $this->editingItem['category_id'],
        ]);

        $this->cancelItemEdit();
    }

    public function cancelItemEdit(): void
    {
        $this->reset('editingItemId', 'editingItem');
    }

    public function deleteItem(int $itemId): void
    {
        FoodItem::findOrFail($itemId)->delete();
    }

    public function render()
    {
        return view('livewire.admin.menu-manager');
    }
}

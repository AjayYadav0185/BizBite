<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-black text-slate-900">Menu Management</h1>
        <p class="text-sm text-slate-500">Add, update and toggle availability of categories & food items — instantly.</p>
    </div>

    {{-- ---------------------------------------------------- ADD CATEGORY --}}
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-3 text-sm font-black uppercase tracking-widest text-slate-500">Add Category</h2>
        <div class="flex flex-wrap items-start gap-3">
            <div class="min-w-[16rem] flex-1">
                <input
                    type="text"
                    wire:model="newCategoryName"
                    placeholder="e.g. Snacks, Beverages, Combos"
                    class="w-full rounded-lg border-slate-300 @error('newCategoryName') border-red-400 @enderror"
                />
                @error('newCategoryName')
                    <p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <button wire:click="createCategory" wire:loading.attr="disabled"
                    class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-slate-700 disabled:opacity-50">
                + Add Category
            </button>
        </div>
    </section>

    {{-- ---------------------------------------------------- ADD FOOD ITEM --}}
    <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <h2 class="mb-3 text-sm font-black uppercase tracking-widest text-slate-500">Add Food Item</h2>
        <form wire:submit="createItem" class="flex flex-wrap items-start gap-3">
            <div>
                <select wire:model="newItem.category_id"
                        class="w-44 rounded-lg border-slate-300 @error('newItem.category_id') border-red-400 @enderror">
                    <option value="">Category…</option>
                    @foreach ($this->categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                @error('newItem.category_id')
                    <p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <div class="min-w-[12rem] flex-1">
                <input type="text" wire:model="newItem.name" placeholder="Item name"
                       class="w-full rounded-lg border-slate-300 @error('newItem.name') border-red-400 @enderror" />
                @error('newItem.name')
                    <p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <input type="number" step="0.01" min="0" wire:model="newItem.price" placeholder="₹ Price"
                       class="w-32 rounded-lg border-slate-300 @error('newItem.price') border-red-400 @enderror" />
                @error('newItem.price')
                    <p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" wire:loading.attr="disabled"
                    class="rounded-lg bg-emerald-500 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-400 disabled:opacity-50">
                + Add Item
            </button>
        </form>
    </section>

    {{-- ---------------------------------------------------- DATA TABLE --}}
    @foreach ($this->categories as $category)
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm" wire:key="cat-section-{{ $category->id }}">
            <header class="flex items-center justify-between border-b border-slate-100 bg-slate-50 px-5 py-3">
                @if ($editingCategoryId === $category->id)
                    <div class="flex items-center gap-2">
                        <input type="text" wire:model="editingCategoryName"
                               class="w-56 rounded-lg border-slate-300 @error('editingCategoryName') border-red-400 @enderror" />
                        <button wire:click="saveCategory" class="rounded-md bg-emerald-500 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-400">Save</button>
                        <button wire:click="cancelCategoryEdit" class="rounded-md bg-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-300">Cancel</button>
                        @error('editingCategoryName')
                            <p class="text-xs font-semibold text-red-500">{{ $message }}</p>
                        @enderror
                    </div>
                @else
                    <h3 class="text-sm font-black uppercase tracking-wide text-slate-700">{{ $category->name }}</h3>
                @endif
                <div class="flex items-center gap-3">
                    <span class="text-xs font-semibold {{ $category->is_active ? 'text-emerald-600' : 'text-slate-400' }}">
                        {{ $category->is_active ? 'Active' : 'Hidden from POS' }}
                    </span>
                    <button wire:click="toggleCategory({{ $category->id }})"
                            class="text-xs font-bold text-slate-500 underline-offset-2 hover:text-slate-900 hover:underline">
                        {{ $category->is_active ? 'Hide' : 'Show' }}
                    </button>
                    <button wire:click="editCategory({{ $category->id }})" class="text-xs font-bold text-blue-500 hover:text-blue-700">Edit</button>
                    <button wire:click="deleteCategory({{ $category->id }})"
                            wire:confirm="Delete category '{{ $category->name }}'?"
                            class="text-xs font-bold text-red-400 hover:text-red-600">Delete</button>
                </div>
            </header>

            <table class="w-full text-left text-sm">
                <thead class="text-xs uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-5 py-2 font-bold">Item</th>
                        <th class="px-5 py-2 font-bold">Price</th>
                        <th class="px-5 py-2 font-bold">Availability</th>
                        <th class="px-5 py-2 text-right font-bold">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($category->foodItems as $item)
                        <tr wire:key="item-{{ $item->id }}" class="hover:bg-slate-50">
                            <td class="px-5 py-3 font-bold text-slate-800">
                                @if ($editingItemId === $item->id)
                                    <div class="flex flex-col gap-1">
                                        <input type="text" wire:model="editingItem.name"
                                               class="w-56 rounded-lg border-slate-300 @error('editingItem.name') border-red-400 @enderror" />
                                        <select wire:model="editingItem.category_id" class="w-56 rounded-lg border-slate-300">
                                            @foreach ($this->categories as $c)
                                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('editingItem.name') <p class="text-xs font-semibold text-red-500">{{ $message }}</p> @enderror
                                    </div>
                                @else
                                    {{ $item->name }}
                                @endif
                            </td>
                            <td class="px-5 py-3 font-black text-slate-700">
                                @if ($editingItemId === $item->id)
                                    <input type="number" step="0.01" min="0" wire:model="editingItem.price"
                                           class="w-24 rounded-lg border-slate-300 @error('editingItem.price') border-red-400 @enderror" />
                                    @error('editingItem.price') <p class="text-xs font-semibold text-red-500">{{ $message }}</p> @enderror
                                @else
                                    ₹{{ $item->price }}
                                @endif
                            </td>
                            <td class="px-5 py-3">
                                <button wire:click="toggleItem({{ $item->id }})"
                                        @class(['rounded-full px-3 py-1 text-xs font-black transition',
                                            $item->is_available ? 'bg-emerald-100 text-emerald-700 hover:bg-emerald-200' : 'bg-slate-200 text-slate-500 hover:bg-slate-300'])>
                                    {{ $item->is_available ? '● Available' : '○ Sold out' }}
                                </button>
                            </td>
                            <td class="px-5 py-3 text-right">
                                @if ($editingItemId === $item->id)
                                    <button wire:click="saveItem" class="rounded-md bg-emerald-500 px-3 py-1.5 text-xs font-bold text-white hover:bg-emerald-400">Save</button>
                                    <button wire:click="cancelItemEdit" class="rounded-md bg-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-300">Cancel</button>
                                @else
                                    <button wire:click="editItem({{ $item->id }})" class="text-xs font-bold text-blue-500 hover:text-blue-700">Edit</button>
                                    <button wire:click="deleteItem({{ $item->id }})"
                                            wire:confirm="Delete '{{ $item->name }}' from the menu?"
                                            class="ml-3 text-xs font-bold text-red-400 hover:text-red-600">Delete</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-6 text-center text-slate-400">No items in this category yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    @endforeach
</div>

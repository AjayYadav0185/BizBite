<div class="space-y-8">
    <div>
        <h1 class="text-2xl font-black text-slate-900">Menu Management</h1>
        <p class="text-sm text-slate-500">Add, update and toggle availability of categories & food items — instantly.</p>
    </div>

    {{-- ---------------------------------------------------- ADD CATEGORY --}}
    <section class="rounded-2xl border border-card-border bg-white p-4 shadow-card sm:p-5">
        <h2 class="mb-3 text-sm font-black uppercase tracking-widest text-slate-500">Add Category</h2>
        <div class="flex flex-col gap-3 sm:flex-row sm:flex-wrap sm:items-start">
            <div class="min-w-[16rem] flex-1">
                <input
                    type="text"
                    wire:model="newCategoryName"
                    placeholder="e.g. Snacks, Beverages, Combos"
                    class="w-full rounded-xl border border-card-border px-4 py-2.5 text-sm outline-none transition focus:border-brand-500 @error('newCategoryName') border-red-400 @enderror"
                />
                @error('newCategoryName')
                    <p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <button wire:click="createCategory" wire:loading.attr="disabled"
                    class="shrink-0 rounded-xl bg-slate-900 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-slate-700 disabled:opacity-50">
                + Add Category
            </button>
        </div>
    </section>

    {{-- ---------------------------------------------------- ADD FOOD ITEM --}}
    <section class="rounded-2xl border border-card-border bg-white p-4 shadow-card sm:p-5">
        <h2 class="mb-3 text-sm font-black uppercase tracking-widest text-slate-500">Add Food Item</h2>
        <form wire:submit="createItem" class="grid grid-cols-1 gap-3 sm:flex sm:flex-wrap sm:items-start">
            <div>
                <select wire:model="newItem.category_id"
                        class="w-full rounded-xl border border-card-border px-4 py-2.5 text-sm outline-none transition focus:border-brand-500 @error('newItem.category_id') border-red-400 @enderror sm:w-40">
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
                       class="w-full rounded-xl border border-card-border px-4 py-2.5 text-sm outline-none transition focus:border-brand-500 @error('newItem.name') border-red-400 @enderror" />
                @error('newItem.name')
                    <p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <input type="number" step="0.01" min="0" wire:model="newItem.price" placeholder="₹ Price"
                       class="w-full rounded-xl border border-card-border px-4 py-2.5 text-sm outline-none transition focus:border-brand-500 @error('newItem.price') border-red-400 @enderror sm:w-36" />
                @error('newItem.price')
                    <p class="mt-1 text-xs font-semibold text-red-500">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit" wire:loading.attr="disabled"
                    class="rounded-xl bg-brand-500 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-brand-400 disabled:opacity-50">
                + Add Item
            </button>
        </form>
    </section>

    {{-- ---------------------------------------------------- DATA TABLE --}}
    @foreach ($this->categories as $category)
        <section class="overflow-hidden rounded-2xl border border-card-border bg-white shadow-card" wire:key="cat-section-{{ $category->id }}">
            <header class="grid grid-cols-1 gap-2 border-b border-surface-subtle bg-surface-muted px-4 py-3 sm:grid-cols-[minmax(0,1fr)_auto] sm:items-center sm:px-5">
                <div class="flex min-w-0 items-center gap-2">
                    @if ($editingCategoryId === $category->id)
                        <input type="text" wire:model="editingCategoryName"
                               class="w-full min-w-0 rounded-xl border border-card-border px-3 py-2 text-sm outline-none transition focus:border-brand-500 @error('editingCategoryName') border-red-400 @enderror" />
                        <button wire:click="saveCategory" class="shrink-0 rounded-xl bg-brand-500 px-3 py-1.5 text-xs font-bold text-white hover:bg-brand-400">Save</button>
                        <button wire:click="cancelCategoryEdit" class="shrink-0 rounded-xl bg-surface-subtle px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-300">Cancel</button>
                        @error('editingCategoryName')
                            <p class="text-xs font-semibold text-red-500">{{ $message }}</p>
                        @enderror
                    @else
                        <h3 class="truncate text-sm font-black uppercase tracking-wide text-slate-700">{{ $category->name }}</h3>
                        <span class="shrink-0 rounded-full px-2.5 py-0.5 text-[11px] font-black {{ $category->is_active ? 'bg-brand-50 text-brand-700' : 'bg-slate-200 text-slate-500' }}">
                            {{ $category->is_active ? 'Active' : 'Hidden from POS' }}
                        </span>
                    @endif
                </div>
                <div class="flex min-w-0 flex-wrap items-center gap-2">
                    <button wire:click="toggleCategory({{ $category->id }})"
                            @class(['rounded-lg px-3 py-1.5 text-xs font-bold transition',
                                $category->is_active ? 'bg-brand-50 text-brand-700 hover:bg-brand-100' : 'bg-brand-500 text-white hover:bg-brand-400'])>
                        {{ $category->is_active ? 'Hide' : 'Show' }}
                    </button>
                    <button wire:click="editCategory({{ $category->id }})" class="rounded-lg bg-surface-muted px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-surface-subtle">Edit</button>
                    <button wire:click="deleteCategory({{ $category->id }})"
                            wire:confirm="Delete category '{{ $category->name }}'?"
                            class="rounded-lg bg-surface-muted px-3 py-1.5 text-xs font-bold text-red-500 hover:bg-red-50">Delete</button>
                </div>
            </header>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[560px] text-left text-sm">
                    <thead class="text-xs uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-4 py-2 font-bold sm:px-5">Item</th>
                            <th class="px-4 py-2 font-bold sm:px-5">Price</th>
                            <th class="px-4 py-2 font-bold sm:px-5">Availability</th>
                            <th class="px-4 py-2 text-right font-bold sm:px-5">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-surface-subtle">
                    @forelse ($category->foodItems as $item)
                        <tr wire:key="item-{{ $item->id }}" class="hover:bg-brand-50/60">
                            <td class="px-4 py-3 font-bold text-slate-800 sm:px-5">
                                @if ($editingItemId === $item->id)
                                    <div class="flex flex-col gap-1">
                                        <input type="text" wire:model="editingItem.name"
                                               class="w-full rounded-xl border border-card-border px-3 py-2 text-sm outline-none transition focus:border-brand-500 @error('editingItem.name') border-red-400 @enderror" />
                                        <select wire:model="editingItem.category_id"
                                                class="w-full rounded-xl border border-card-border px-3 py-2 text-sm outline-none focus:border-brand-500">
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
                            <td class="px-4 py-3 font-black tabular-nums text-slate-700 sm:px-5">
                                @if ($editingItemId === $item->id)
                                    <input type="number" step="0.01" min="0" wire:model="editingItem.price"
                                           class="w-28 rounded-xl border border-card-border px-3 py-2 text-sm outline-none transition focus:border-brand-500 @error('editingItem.price') border-red-400 @enderror" />
                                    @error('editingItem.price') <p class="text-xs font-semibold text-red-500">{{ $message }}</p> @enderror
                                @else
                                    ₹{{ $item->price }}
                                @endif
                            </td>
                            <td class="px-4 py-3 sm:px-5">
                                <button wire:click="toggleItem({{ $item->id }})"
                                        @class(['rounded-full px-3 py-1 text-xs font-black transition',
                                            $item->is_available ? 'bg-brand-50 text-brand-700 hover:bg-brand-100' : 'bg-slate-200 text-slate-500 hover:bg-slate-300'])>
                                    {{ $item->is_available ? '● Available' : '○ Sold out' }}
                                </button>
                            </td>
                            <td class="px-4 py-3 text-right whitespace-nowrap sm:px-5">
                                @if ($editingItemId === $item->id)
                                    <button wire:click="saveItem" class="rounded-xl bg-brand-500 px-3 py-1.5 text-xs font-bold text-white hover:bg-brand-400">Save</button>
                                    <button wire:click="cancelItemEdit" class="rounded-xl bg-slate-200 px-3 py-1.5 text-xs font-bold text-slate-600 hover:bg-slate-300">Cancel</button>
                                @else
                                    <button wire:click="editItem({{ $item->id }})" class="text-xs font-bold text-teal-600 hover:text-teal-700">Edit</button>
                                    <button wire:click="deleteItem({{ $item->id }})"
                                            wire:confirm="Delete '{{ $item->name }}' from the menu?"
                                            class="ml-3 text-xs font-bold text-red-400 hover:text-red-600">Delete</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-slate-400 sm:px-5">No items in this category yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </section>
    @endforeach
</div>

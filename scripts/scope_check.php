<?php

use App\Models\Category;
use App\Models\FoodItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Store;
use App\Models\User;
use Illuminate\Support\Facades\Context;

// Boot every model so global scopes register / errors surface.
Store::query()->count();
User::query()->count();
Category::query()->count();
FoodItem::query()->count();
Order::query()->count();
OrderItem::query()->count();

dump('=== Models booted without error ===');

// No tenant in context => scope is a no-op.
dump(Order::query()->toSql());
dump(OrderItem::query()->toSql());

// With tenant set => scope applies.
Context::add('current_store_id', 42);

dump(Order::query()->toSql());
dump(OrderItem::query()->toSql());
dump(FoodItem::query()->toSql());

Context::forget('current_store_id');
dump('=== Done ===');
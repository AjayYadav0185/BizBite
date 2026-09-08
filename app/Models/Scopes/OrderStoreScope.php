<?php

namespace App\Models\Scopes;

use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Context;

/**
 * Global tenant scope for models that don't carry a `store_id` column.
 *
 * Order items are constrained to the current store by filtering on the
 * `order_id`s that belong to the authenticated store. This keeps every
 * business query automatically tenant scoped even for child tables.
 */
class OrderStoreScope implements Scope
{
    /**
     * Apply the scope to the given Eloquent query builder.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $builder
     * @param  \Illuminate\Database\Eloquent\Model  $model
     * @return void
     */
    public function apply(Builder $builder, Model $model)
    {
        $storeId = Context::get('current_store_id');

        if ($storeId) {
            // Use an unscoped order subquery to avoid the subquery re-applying
            // Order's own StoreScope (the store_id is already explicit here).
            $builder->whereIn(
                'order_id',
                Order::query()->withoutGlobalScopes()->select('id')->where(
                    'store_id', $storeId
                )
            );
        }
    }
}
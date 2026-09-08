<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;
use Illuminate\Support\Facades\Context;

/**
 * Global tenant scope.
 *
 * Every query on a model decorated with `#[ScopedBy(StoreScope::class)]` is
 * automatically constrained to the `store_id` of the currently authenticated
 * user. The store id is resolved from the request scoped `Context`, which is
 * populated for both web (session) and API (Sanctum bearer token) requests by
 * the `SetCurrentStore` middleware.
 *
 * When no tenant is active (e.g. console commands, seeders) the scope is a
 * no-op so operations are not accidentally blocked.
 */
class StoreScope implements Scope
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
            $builder->where($model->qualifyColumn('store_id'), $storeId);
        }
    }
}
<?php

namespace App\Models;

use App\Models\Scopes\StoreScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy(StoreScope::class)]
class SyncQueueItem extends Model
{
    use HasFactory;

    protected $table = 'tbl_sync_queue';

    protected $fillable = [
        'store_id',
        'user_id',
        'device_id',
        'action',
        'idempotency_key',
        'payload',
        'status',
        'attempts',
        'error',
        'applied_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'attempts' => 'integer',
            'applied_at' => 'datetime',
        ];
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

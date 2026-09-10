<?php

namespace App\Models;

use App\Models\Scopes\StoreScope;
use Illuminate\Database\Eloquent\Attributes\ScopedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[ScopedBy(StoreScope::class)]
class StoreDevice extends Model
{
    use HasFactory;

    protected $table = 'tbl_pos_store_devices';

    protected $fillable = [
        'store_id',
        'user_id',
        'device_id',
        'platform',
        'app_version',
        'fcm_token',
        'last_sync_at',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'last_sync_at' => 'datetime',
            'is_active' => 'boolean',
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

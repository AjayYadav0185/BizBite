<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Request;

/**
 * Single choke-point for writing owner-visible audit logs.
 *
 * Every business mutation that money or trust depends on (price changes,
 * discounts, cancellations, credit bills, settings edits, staff changes)
 * calls `Audit::record(...)`. Cashiers can CREATE log rows through the
 * actions they perform, but only admins can READ them (gate
 * `view-audit-logs`; Livewire AuditLogViewer enforces it in mount()).
 */
final class Audit
{
    /**
     * @param  array<string, mixed>|null  $old  Before-values (JSON).
     * @param  array<string, mixed>|null  $new  After-values (JSON).
     */
    public static function record(
        User $actor,
        string $action,
        string $description,
        ?string $entityType = null,
        ?int $entityId = null,
        ?string $entityName = null,
        ?array $old = null,
        ?array $new = null,
        string|float|int|null $amount = null,
    ): AuditLog {
        return AuditLog::withoutGlobalScopes()->create([
            'store_id' => $actor->store_id,
            'user_id' => $actor->id,
            'user_name' => $actor->name,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'entity_name' => $entityName,
            'description' => $description,
            'old_values' => $old,
            'new_values' => $new,
            'amount' => $amount,
            'ip_address' => Request::ip(),
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventoryAuditSession extends Model
{
    public const OPEN = 'In Progress';

    protected $table = 'inventory_audit';
    protected $primaryKey = 'audit_id';
    protected $fillable = ['conducted_by', 'started_at', 'completed_at', 'audit_status', 'remarks'];

    public function items(): HasMany
    {
        return $this->hasMany(InventoryAuditItem::class, 'audit_id');
    }
}

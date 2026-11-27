<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryAuditItem extends Model
{
    protected $table = 'inventory_audit_item';
    protected $primaryKey = 'audit_item_id';
    protected $fillable = ['verification_status', 'observed_condition', 'discrepancy_type', 'remarks', 'verified_at'];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class, 'book_id')->withTrashed();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookRepair extends Model
{
    protected $table = 'book_repair';
    protected $primaryKey = 'repair_id';
    protected $fillable = ['transaction_id', 'scheduled_on', 'completed_at', 'outcome'];

    public function loan(): BelongsTo
    {
        return $this->belongsTo(BorrowTransaction::class, 'transaction_id');
    }
}

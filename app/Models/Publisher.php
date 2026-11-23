<?php

namespace App\Models;

use App\Models\Concerns\LogsCatalogActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['publisher_name', 'publisher_type'])]
class Publisher extends Model
{
    use HasFactory, LogsCatalogActivity;

    protected $table = 'publisher';
    protected $primaryKey = 'publisher_id';
    public $timestamps = false;

    public function books(): BelongsToMany
    {
        return $this->belongsToMany(Book::class, 'book_publisher', 'publisher_id', 'book_id');
    }
}

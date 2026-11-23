<?php

namespace App\Models;

use App\Models\Concerns\LogsCatalogActivity;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['genre_name'])]
class Genre extends Model
{
    use HasFactory, LogsCatalogActivity;

    protected $table = 'genre';
    protected $primaryKey = 'genre_id';
    public $timestamps = false;

    public function books(): HasMany
    {
        return $this->hasMany(Book::class, 'genre_id');
    }
}

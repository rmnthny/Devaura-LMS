<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\Pivot;

class BookPublisher extends Pivot
{
    use HasFactory;

    protected $table = 'book_publisher';
    public $incrementing = false;
    public $timestamps = false;
}

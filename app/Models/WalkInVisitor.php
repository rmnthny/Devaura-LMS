<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['last_name', 'first_name', 'middle_initial', 'email'])]
class WalkInVisitor extends Model
{
    use HasFactory;

    protected $table = 'walk_in_visitor';
    protected $primaryKey = 'visitor_id';
    public $timestamps = false;
}

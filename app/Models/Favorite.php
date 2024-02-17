<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Favorite extends Model
{
    use HasFactory;

    protected $table = 'favorites';

    public $timestamps = true; //by default timestamp false

    protected $fillable = ['pid','type','uid'];

    protected $hidden = [
        'updated_at', 'created_at',
    ];
}

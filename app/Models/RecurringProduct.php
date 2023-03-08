<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecurringProduct extends Model
{
    use HasFactory;


    protected $fillable = [
        'name',
        'description',
        'sku',
        'price',
    ];

    //translatable fields
    public $translatable = ['name'];
}

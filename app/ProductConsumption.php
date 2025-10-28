<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductConsumption extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'location_id',
        'transaction_id',
        'quantity_entry',
        'quantity_sorty',
        'date_entry',
        'date_sorty',
    ];
}
